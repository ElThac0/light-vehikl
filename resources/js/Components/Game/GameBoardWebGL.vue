<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import { CRASHED_COLOR, PLAYER_COLORS, toRgb } from "./playerColors.js";
import { createProgram } from "./webgl.js";

// Same props as GameBoard.vue, so the two can be swapped freely.
const props = defineProps({
  arenaSize: Number,
  board: {},
  players: {},
})

const canvas = ref(null);
const supported = ref(true);

let gl = null;
let program = null;
let texture = null;
let uniforms = {};
let resizeObserver = null;

// Draws a single full-canvas quad; the fragment shader works out which tile
// each pixel belongs to by looking the tile up in the board texture.
const VERTEX_SHADER = `
attribute vec2 a_position;
varying vec2 v_uv;

void main() {
  // uv runs 0..1 left to right and top to bottom, like the HTML grid.
  v_uv = vec2(a_position.x * 0.5 + 0.5, 0.5 - a_position.y * 0.5);
  gl_Position = vec4(a_position, 0.0, 1.0);
}
`;

// Tile contents follow the ContentType enum: 0 empty, 1 wall, then each
// player's head (2, 4, 6, 8) followed by their trail (3, 5, 7, 9).
const FRAGMENT_SHADER = `
precision mediump float;

varying vec2 v_uv;

uniform sampler2D u_board;
uniform float u_size;
uniform float u_cellPixels;
uniform vec4 u_crashed;
uniform vec3 u_playerColors[4];
uniform vec3 u_crashedColor;

const vec3 BACKGROUND = vec3(0.02, 0.02, 0.07);
const vec3 GRID = vec3(0.0, 0.0, 0.5);
const vec3 WALL = vec3(0.45, 0.45, 0.5);

// WebGL 1 fragment shaders can't index uniform arrays with a variable.
vec3 playerColor(float player) {
  if (player < 0.5) return u_playerColors[0];
  if (player < 1.5) return u_playerColors[1];
  if (player < 2.5) return u_playerColors[2];
  return u_playerColors[3];
}

float playerCrashed(float player) {
  if (player < 0.5) return u_crashed.x;
  if (player < 1.5) return u_crashed.y;
  if (player < 2.5) return u_crashed.z;
  return u_crashed.w;
}

void main() {
  vec2 position = v_uv * u_size;
  vec2 cell = floor(position);
  float contents = floor(texture2D(u_board, (cell + 0.5) / u_size).r * 255.0 + 0.5);

  vec3 color = BACKGROUND;

  if (contents > 1.5) {
    float player = floor((contents - 2.0) / 2.0);
    bool head = mod(contents, 2.0) < 0.5;
    vec3 base = playerColor(player);

    if (head && playerCrashed(player) > 0.5) {
      color = u_crashedColor;
    } else if (head) {
      // A brighter core so each rider stands out from their trail.
      color = mix(base, vec3(1.0), 0.45);
    } else {
      // Trails glow brightest along their centre line.
      vec2 fromCentre = abs(fract(position) - 0.5) * 2.0;
      float glow = 1.0 - 0.4 * max(fromCentre.x, fromCentre.y);
      color = base * glow;
    }
  } else if (contents > 0.5) {
    color = WALL;
  }

  // One-pixel grid lines around every tile, like the HTML board's borders.
  vec2 inCell = fract(position) * u_cellPixels;
  float edge = min(min(inCell.x, inCell.y), min(u_cellPixels - inCell.x, u_cellPixels - inCell.y));
  color = mix(GRID, color, smoothstep(0.5, 1.0, edge));

  gl_FragColor = vec4(color, 1.0);
}
`;

const init = () => {
  gl = canvas.value.getContext('webgl', { antialias: false });

  if (!gl) {
    supported.value = false;
    return;
  }

  program = createProgram(gl, VERTEX_SHADER, FRAGMENT_SHADER);
  gl.useProgram(program);

  // Two triangles covering the whole canvas.
  gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
  gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, -1, 1, 1, -1, 1, 1]), gl.STATIC_DRAW);
  const position = gl.getAttribLocation(program, 'a_position');
  gl.enableVertexAttribArray(position);
  gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);

  // One texel per tile holding its contents. NEAREST keeps tiles crisp, and
  // CLAMP_TO_EDGE is required for textures that aren't a power of two.
  texture = gl.createTexture();
  gl.bindTexture(gl.TEXTURE_2D, texture);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.NEAREST);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.NEAREST);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
  // Rows are one byte per tile, so they aren't 4-byte aligned.
  gl.pixelStorei(gl.UNPACK_ALIGNMENT, 1);

  uniforms = Object.fromEntries(['u_board', 'u_size', 'u_cellPixels', 'u_crashed', 'u_playerColors', 'u_crashedColor']
    .map((name) => [name, gl.getUniformLocation(program, name)]));
  gl.uniform1i(uniforms.u_board, 0);
  gl.uniform3fv(uniforms.u_playerColors, PLAYER_COLORS.flatMap(toRgb));
  gl.uniform3fv(uniforms.u_crashedColor, toRgb(CRASHED_COLOR));
}

const draw = () => {
  const size = props.arenaSize;

  if (!gl || gl.isContextLost() || !size || props.board?.length !== size * size) {
    return;
  }

  gl.bindTexture(gl.TEXTURE_2D, texture);
  gl.texImage2D(gl.TEXTURE_2D, 0, gl.LUMINANCE, size, size, 0, gl.LUMINANCE, gl.UNSIGNED_BYTE, Uint8Array.from(props.board));

  const crashed = [2, 4, 6, 8].map((slot) => props.players?.find((player) => player.slot === slot)?.status === 'crashed' ? 1 : 0);

  gl.viewport(0, 0, gl.drawingBufferWidth, gl.drawingBufferHeight);
  gl.uniform1f(uniforms.u_size, size);
  gl.uniform1f(uniforms.u_cellPixels, gl.drawingBufferWidth / size);
  gl.uniform4fv(uniforms.u_crashed, crashed);
  gl.drawArrays(gl.TRIANGLES, 0, 6);
}

// Match the drawing buffer to the canvas's on-screen size so it stays sharp.
const resize = () => {
  const pixels = Math.round(canvas.value.clientWidth * window.devicePixelRatio);

  // Check both sides: a new canvas is 300x150, so width alone can already match.
  if (pixels > 0 && (canvas.value.width !== pixels || canvas.value.height !== pixels)) {
    canvas.value.width = canvas.value.height = pixels;
  }

  draw();
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
  canvas.value.removeEventListener('webglcontextlost', onContextLost);
  canvas.value.removeEventListener('webglcontextrestored', onContextRestored);

  // Browsers cap live WebGL contexts, so release this one rather than
  // waiting for garbage collection when switching render modes.
  gl?.getExtension('WEBGL_lose_context')?.loseContext();
  gl = null;
});

watch(() => [props.board, props.players, props.arenaSize], draw);
</script>

<template>
  <canvas v-show="supported" ref="canvas" id="board-webgl" role="img" aria-label="Game board"></canvas>
  <p v-if="!supported" class="text-center py-8">
    Your browser doesn't support WebGL. Switch to the HTML board to play.
  </p>
</template>

<style>
#board-webgl {
  display: block;
  width: 100%;
  max-width: 75vh;
  aspect-ratio: 1;
  margin: auto;
}
</style>
