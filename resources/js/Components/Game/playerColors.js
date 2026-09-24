/**
 * The one place player colours are defined. Every board and the player
 * list read them from here, so they always match.
 *
 * Indexed by player number minus one; a player's slot (their head's
 * ContentType value: 2, 4, 6, 8) maps to index slot / 2 - 1.
 */
export const PLAYER_COLORS = ['#00ff00', '#00eaff', '#2563eb', '#ffa500'];

export const CRASHED_COLOR = '#ff0000';

export const playerColor = (slot) => PLAYER_COLORS[slot / 2 - 1];

/**
 * A "#rrggbb" colour as 0..1 floats, the form WebGL wants.
 */
export const toRgb = (hex) => [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255);

// Expose the colours as CSS variables for the HTML board's tile styles.
if (typeof document !== 'undefined') {
  PLAYER_COLORS.forEach((color, i) => document.documentElement.style.setProperty(`--player-${i + 1}-color`, color));
  document.documentElement.style.setProperty('--player-crashed-color', CRASHED_COLOR);
}
