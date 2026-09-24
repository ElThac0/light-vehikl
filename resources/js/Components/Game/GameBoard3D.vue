<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import { CRASHED_COLOR, PLAYER_COLORS, toRgb } from "./playerColors.js";
import { createProgram } from "./webgl.js";

// Same props as GameBoard.vue, so the boards can be swapped freely.
const props = defineProps({
  arenaSize: Number,
  board: {},
  players: {},
})

const canvas = ref(null);
const supported = ref(true);
const dragging = ref(false);

const PLAYER_RGB = PLAYER_COLORS.map(toRgb);
const CRASHED = toRgb(CRASHED_COLOR);
const WALL = [0.45, 0.45, 0.5];
const BACKGROUND = [0.02, 0.02, 0.07];
const FLOOR = [0.04, 0.04, 0.12, 1];
const GRID = [0, 0, 0.5, 1];
const EDGES = [0.25, 0.4, 1, 1];

// Floats per cube in the instance buffer: x, y, z, r, g, b, scale.
const INSTANCE_FLOATS = 7;

// Lit cubes. Each instance moves and scales a unit cube onto its tile.
const CUBE_VERTEX_SHADER = `#version 300 es
layout(location = 0) in vec3 a_position;
layout(location = 1) in vec3 a_normal;
layout(location = 2) in vec3 a_offset;
layout(location = 3) in vec4 a_colorScale;

uniform mat4 u_viewProjection;

out vec3 v_normal;
out vec3 v_color;

void main() {
  v_normal = a_normal;
  v_color = a_colorScale.rgb;
  gl_Position = u_viewProjection * vec4(a_position * a_colorScale.a + a_offset, 1.0);
}
`;

const CUBE_FRAGMENT_SHADER = `#version 300 es
precision mediump float;

in vec3 v_normal;
in vec3 v_color;

out vec4 outColor;

const vec3 LIGHT = normalize(vec3(0.4, 1.0, 0.6));

void main() {
  float light = 0.45 + 0.55 * max(dot(normalize(v_normal), LIGHT), 0.0);
  outColor = vec4(v_color * light, 1.0);
}
`;

// Unlit, single-colour geometry: the floor, its grid and the arena's edges.
const FLAT_VERTEX_SHADER = `#version 300 es
layout(location = 0) in vec3 a_position;

uniform mat4 u_viewProjection;

void main() {
  gl_Position = u_viewProjection * vec4(a_position, 1.0);
}
`;

const FLAT_FRAGMENT_SHADER = `#version 300 es
precision mediump float;

uniform vec4 u_color;

out vec4 outColor;

void main() {
  outColor = u_color;
}
`;

// Camera orbits the arena's centre. yaw 0 looks from the front, so the
// board reads the same way round as the HTML board.
const camera = { yaw: 0, pitch: 0.6, zoom: 1 };
const MAX_PITCH = 1.5;
const DRAG_SPEED = 0.008;

// Zoom multiplies the camera's distance; 1 fits the whole arena in view,
// the minimum puts the camera near the centre of the arena.
const MIN_ZOOM = 0.25;
const MAX_ZOOM = 2.5;
const ZOOM_SPEED = 0.0015;

let gl = null;
let cubeProgram, flatProgram;
let cubeVao, floorVao, gridVao, edgesVao;
let instanceBuffer;
let instanceCount = 0;
let gridVertexCount = 0;
let geometrySize = null;
let uniforms = {};
let resizeObserver = null;
let frame = 0;
let dragFrom = null;

// --- Matrices (column-major, as WebGL expects) ---

const perspective = (fovY, aspect, near, far) => {
  const f = 1 / Math.tan(fovY / 2);
  const nf = 1 / (near - far);

  return [f / aspect, 0, 0, 0, 0, f, 0, 0, 0, 0, (far + near) * nf, -1, 0, 0, 2 * far * near * nf, 0];
}

const lookAt = (eye, target) => {
  const sub = (a, b) => a.map((v, i) => v - b[i]);
  const cross = (a, b) => [a[1] * b[2] - a[2] * b[1], a[2] * b[0] - a[0] * b[2], a[0] * b[1] - a[1] * b[0]];
  const dot = (a, b) => a[0] * b[0] + a[1] * b[1] + a[2] * b[2];
  const normalize = (a) => a.map((v) => v / Math.hypot(...a));

  const z = normalize(sub(eye, target));
  const x = normalize(cross([0, 1, 0], z));
  const y = cross(z, x);

  return [x[0], y[0], z[0], 0, x[1], y[1], z[1], 0, x[2], y[2], z[2], 0, -dot(x, eye), -dot(y, eye), -dot(z, eye), 1];
}

const multiply = (a, b) => Array.from({ length: 16 }, (_, i) => {
  const column = Math.floor(i / 4);
  const row = i % 4;

  return a[row] * b[column * 4] + a[4 + row] * b[column * 4 + 1] + a[8 + row] * b[column * 4 + 2] + a[12 + row] * b[column * 4 + 3];
});

// --- Geometry ---

// A unit cube centred on the origin: 6 faces of 2 triangles, with normals.
const cubeVertices = () => {
  const faces = [
    [[1, 0, 0], [0, 1, 0], [0, 0, 1]],
    [[-1, 0, 0], [0, 1, 0], [0, 0, -1]],
    [[0, 1, 0], [0, 0, 1], [1, 0, 0]],
    [[0, -1, 0], [0, 0, -1], [1, 0, 0]],
    [[0, 0, 1], [1, 0, 0], [0, 1, 0]],
    [[0, 0, -1], [-1, 0, 0], [0, 1, 0]],
  ];

  return faces.flatMap(([normal, u, v]) => [[-1, -1], [1, -1], [1, 1], [-1, -1], [1, 1], [-1, 1]]
    .flatMap(([a, b]) => [
      ...normal.map((n, i) => n * 0.5 + u[i] * a * 0.5 + v[i] * b * 0.5),
      ...normal,
    ]));
}

const flatVao = (vertices) => {
  const vao = gl.createVertexArray();
  gl.bindVertexArray(vao);
  gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
  gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(vertices), gl.STATIC_DRAW);
  gl.enableVertexAttribArray(0);
  gl.vertexAttribPointer(0, 3, gl.FLOAT, false, 0, 0);
  gl.bindVertexArray(null);

  return vao;
}

// The floor, grid and arena outline depend on the arena size, so they're
// (re)built whenever it changes. The arena spans -h..h across and deep,
// and 0..size high.
const buildArena = (size) => {
  const h = size / 2;

  // Wound anticlockwise seen from above, so it's culled from below and
  // the board stays visible when orbiting underneath.
  floorVao = flatVao([-h, 0, h, h, 0, h, h, 0, -h, -h, 0, h, h, 0, -h, -h, 0, -h]);

  const grid = [];
  for (let i = 0; i <= size; i++) {
    const p = i - h;
    grid.push(p, 0, -h, p, 0, h, -h, 0, p, h, 0, p);
  }
  gridVao = flatVao(grid);
  gridVertexCount = grid.length / 3;

  const corners = [[-h, 0, -h], [h, 0, -h], [h, 0, h], [-h, 0, h]];
  const edges = corners.flatMap((corner, i) => {
    const next = corners[(i + 1) % 4];
    const top = (c) => [c[0], size, c[2]];

    return [...corner, ...next, ...top(corner), ...top(next), ...corner, ...top(corner)];
  });
  edgesVao = flatVao(edges);

  geometrySize = size;
}

// One cube per occupied tile. Tiles run row by row, like the HTML grid:
// columns go left to right along x, rows go back to front along z.
const buildInstances = (size) => {
  const crashed = new Set(props.players?.filter((player) => player.status === 'crashed').map((player) => player.slot));
  const data = new Float32Array(size * size * INSTANCE_FLOATS);
  let count = 0;

  props.board.forEach((contents, index) => {
    if (contents === 0) {
      return;
    }

    let color = WALL;
    let scale = 1;

    if (contents >= 2) {
      const base = PLAYER_RGB[Math.floor((contents - 2) / 2)];
      const head = contents % 2 === 0;

      if (head && crashed.has(contents)) {
        color = CRASHED;
      } else if (head) {
        // A brighter, slightly bigger cube marks each rider.
        color = base.map((c) => c + (1 - c) * 0.45);
      } else {
        color = base.map((c) => c * 0.85);
        scale = 0.8;
      }
    }

    const x = index % size - size / 2 + 0.5;
    const z = Math.floor(index / size) - size / 2 + 0.5;

    data.set([x, scale / 2, z, ...color, scale], count * INSTANCE_FLOATS);
    count++;
  });

  gl.bindBuffer(gl.ARRAY_BUFFER, instanceBuffer);
  gl.bufferData(gl.ARRAY_BUFFER, data.subarray(0, count * INSTANCE_FLOATS), gl.DYNAMIC_DRAW);
  instanceCount = count;
}

// --- Setup and drawing ---

const init = () => {
  gl = canvas.value.getContext('webgl2', { antialias: true });

  if (!gl) {
    supported.value = false;
    return;
  }

  cubeProgram = createProgram(gl, CUBE_VERTEX_SHADER, CUBE_FRAGMENT_SHADER);
  flatProgram = createProgram(gl, FLAT_VERTEX_SHADER, FLAT_FRAGMENT_SHADER);

  uniforms = {
    cubeViewProjection: gl.getUniformLocation(cubeProgram, 'u_viewProjection'),
    flatViewProjection: gl.getUniformLocation(flatProgram, 'u_viewProjection'),
    flatColor: gl.getUniformLocation(flatProgram, 'u_color'),
  };

  cubeVao = gl.createVertexArray();
  gl.bindVertexArray(cubeVao);

  gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
  gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(cubeVertices()), gl.STATIC_DRAW);
  gl.enableVertexAttribArray(0);
  gl.vertexAttribPointer(0, 3, gl.FLOAT, false, 24, 0);
  gl.enableVertexAttribArray(1);
  gl.vertexAttribPointer(1, 3, gl.FLOAT, false, 24, 12);

  instanceBuffer = gl.createBuffer();
  gl.bindBuffer(gl.ARRAY_BUFFER, instanceBuffer);
  gl.enableVertexAttribArray(2);
  gl.vertexAttribPointer(2, 3, gl.FLOAT, false, INSTANCE_FLOATS * 4, 0);
  gl.vertexAttribDivisor(2, 1);
  gl.enableVertexAttribArray(3);
  gl.vertexAttribPointer(3, 4, gl.FLOAT, false, INSTANCE_FLOATS * 4, 12);
  gl.vertexAttribDivisor(3, 1);

  gl.bindVertexArray(null);

  gl.enable(gl.DEPTH_TEST);
  gl.clearColor(...BACKGROUND, 1);

  geometrySize = null;
}

const viewProjection = (size) => {
  const target = [0, size / 2, 0];
  // Far enough back that the whole arena cube fits in view from any angle.
  const distance = size * 2.3 * camera.zoom;
  const eye = [
    target[0] + distance * Math.cos(camera.pitch) * Math.sin(camera.yaw),
    target[1] + distance * Math.sin(camera.pitch),
    target[2] + distance * Math.cos(camera.pitch) * Math.cos(camera.yaw),
  ];
  const aspect = gl.drawingBufferWidth / gl.drawingBufferHeight;

  return new Float32Array(multiply(perspective(Math.PI / 4, aspect, size * 0.05, size * 10), lookAt(eye, target)));
}

const draw = () => {
  const size = props.arenaSize;

  if (!gl || gl.isContextLost() || !size || props.board?.length !== size * size) {
    return;
  }

  if (geometrySize !== size) {
    buildArena(size);
  }

  buildInstances(size);

  const matrix = viewProjection(size);

  gl.viewport(0, 0, gl.drawingBufferWidth, gl.drawingBufferHeight);
  gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);

  gl.useProgram(flatProgram);
  gl.uniformMatrix4fv(uniforms.flatViewProjection, false, matrix);

  // Push the floor back a touch so the grid lines drawn on it don't flicker.
  gl.enable(gl.CULL_FACE);
  gl.enable(gl.POLYGON_OFFSET_FILL);
  gl.polygonOffset(1, 1);
  gl.uniform4fv(uniforms.flatColor, FLOOR);
  gl.bindVertexArray(floorVao);
  gl.drawArrays(gl.TRIANGLES, 0, 6);
  gl.disable(gl.POLYGON_OFFSET_FILL);
  gl.disable(gl.CULL_FACE);

  gl.uniform4fv(uniforms.flatColor, GRID);
  gl.bindVertexArray(gridVao);
  gl.drawArrays(gl.LINES, 0, gridVertexCount);

  gl.uniform4fv(uniforms.flatColor, EDGES);
  gl.bindVertexArray(edgesVao);
  gl.drawArrays(gl.LINES, 0, 24);

  if (instanceCount > 0) {
    gl.useProgram(cubeProgram);
    gl.uniformMatrix4fv(uniforms.cubeViewProjection, false, matrix);
    gl.bindVertexArray(cubeVao);
    gl.drawArraysInstanced(gl.TRIANGLES, 0, 36, instanceCount);
  }

  gl.bindVertexArray(null);
}

// Coalesce redraws (e.g. many pointer moves) into one per frame.
const requestDraw = () => {
  if (!frame) {
    frame = requestAnimationFrame(() => {
      frame = 0;
      draw();
    });
  }
}

const resize = () => {
  const width = Math.round(canvas.value.clientWidth * window.devicePixelRatio);
  const height = Math.round(canvas.value.clientHeight * window.devicePixelRatio);

  if (width > 0 && (canvas.value.width !== width || canvas.value.height !== height)) {
    canvas.value.width = width;
    canvas.value.height = height;
  }

  draw();
}

// --- Orbiting with the left mouse button, zooming with the wheel ---

const onPointerDown = (e) => {
  if (e.button !== 0) {
    return;
  }

  e.preventDefault();
  dragFrom = { x: e.clientX, y: e.clientY };
  dragging.value = true;
  canvas.value.setPointerCapture(e.pointerId);
}

const onPointerMove = (e) => {
  if (!dragFrom) {
    return;
  }

  camera.yaw -= (e.clientX - dragFrom.x) * DRAG_SPEED;
  camera.pitch = Math.min(MAX_PITCH, Math.max(-MAX_PITCH, camera.pitch + (e.clientY - dragFrom.y) * DRAG_SPEED));
  dragFrom = { x: e.clientX, y: e.clientY };

  requestDraw();
}

const onPointerUp = () => {
  dragFrom = null;
  dragging.value = false;
}

const onWheel = (e) => {
  // Firefox can report the wheel in lines rather than pixels.
  const pixels = e.deltaMode === WheelEvent.DOM_DELTA_LINE ? e.deltaY * 16 : e.deltaY;

  camera.zoom = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, camera.zoom * Math.exp(pixels * ZOOM_SPEED)));

  requestDraw();
}

const onContextLost = (e) => e.preventDefault();

const onContextRestored = () => {
  init();
  draw();
}

onMounted(() => {
  canvas.value.addEventListener('webglcontextlost', onContextLost);
  canvas.value.addEventListener('webglcontextrestored', onContextRestored);

  init();

  resizeObserver = new ResizeObserver(resize);
  resizeObserver.observe(canvas.value);
});

onBeforeUnmount(() => {
  resizeObserver?.disconnect();
  cancelAnimationFrame(frame);
  canvas.value.removeEventListener('webglcontextlost', onContextLost);
  canvas.value.removeEventListener('webglcontextrestored', onContextRestored);

  // Browsers cap live WebGL contexts, so release this one rather than
  // waiting for garbage collection when switching render modes.
  gl?.getExtension('WEBGL_lose_context')?.loseContext();
  gl = null;
});

watch(() => [props.board, props.players, props.arenaSize], requestDraw);
</script>

<template>
  <canvas
    v-show="supported"
    ref="canvas"
    id="board-3d"
    :class="{ dragging }"
    role="img"
    aria-label="Game board in 3D. Drag with the left mouse button to rotate the view, and scroll to zoom."
    @pointerdown="onPointerDown"
    @pointermove="onPointerMove"
    @pointerup="onPointerUp"
    @pointercancel="onPointerUp"
    @wheel.prevent="onWheel"
  ></canvas>
  <p v-if="!supported" class="text-center py-8">
    Your browser doesn't support WebGL 2. Switch to the HTML board to play.
  </p>
</template>

<style>
#board-3d {
  display: block;
  width: 100%;
  max-width: 75vh;
  aspect-ratio: 1;
  margin: auto;
  cursor: grab;
  /* Let drags rotate the view instead of scrolling the page on touch screens. */
  touch-action: none;
}

#board-3d.dragging {
  cursor: grabbing;
}
</style>
