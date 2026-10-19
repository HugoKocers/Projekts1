# Project README

This project includes:

- Laravel backend
- Vue frontend
- SQLite database setup

The app is organized into separate backend, frontend, and database folders for clearer project structure.

## Run the game locally

1. In `backend/laravel-app`, copy `.env.example` to `.env`, then configure `GAME_ADMIN_EMAIL`, `GAME_ADMIN_USERNAME`, and a strong `GAME_ADMIN_PASSWORD`. The account is created as an administrator when seeding; normal sign-ups never receive admin access.
2. From `backend/laravel-app`, run:

   ```powershell
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve --host=127.0.0.1 --port=8000
   ```

3. In a second terminal, from `frontend`, run `npm install` and `npm run dev`.

The game frontend runs on port `5173`; Laravel, the game API, runs on port `8000`. These are separate servers and cannot share a port. Vite proxies `/game-api` requests to Laravel on port 8000. Open `http://127.0.0.1:5173` for the game, and keep both server terminals running. Sign in or create an account before starting a game; Continue lists that account's unfinished sessions with the current scenario description and elapsed time in `MM:SS` (up to `99:59`), and keeps the three most recently updated saves. The configured admin can open the Admin Panel from the main menu to manage scenarios, their three choices, and items. A scenario can optionally replace a selected normal scenario at random; replacement chances for the same normal scenario must total no more than `1` (100%). Spawn and reward chances use increments of `0.01` (for example, `0.25` is 25%). When deleting a scenario, existing incoming choices retain their endings and completed saves at that scene are deleted; deletion is blocked if an unfinished save, an incoming choice without a complete ending, or a random replacement still depends on it.
