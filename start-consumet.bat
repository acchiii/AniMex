@echo off
cd /d "%~dp0AniMex\consumet-server"
echo Starting Consumet API server...
echo Providers: AnimeSaturn, AnimeUnity, AnimeKai, Hianime
echo.
node server.js
pause
