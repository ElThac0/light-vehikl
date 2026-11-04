/**
 * Pull a human-readable message out of an axios error.
 *
 * Game rule violations (GameException) respond with a bare JSON string, while
 * Laravel's default handler (404s, 419s, 500s, validation) responds with
 * `{ message: "..." }`. Handle both, falling back to the axios error message.
 */
export default function errorMessage(e) {
  const data = e?.response?.data;

  if (typeof data === 'string' && data !== '') {
    return data;
  }

  if (data && typeof data.message === 'string' && data.message !== '') {
    return data.message;
  }

  return e?.message ?? 'Unknown error';
}
