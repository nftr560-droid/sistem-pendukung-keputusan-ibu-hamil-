@echo off
title SPKK Hipertensi - Online via ngrok
color 0b
echo ==============================================================
echo   SPKK HIPERTENSI KEHAMILAN (CDSS) - ONLINE VIA NGROK
echo ==============================================================
echo.
echo [1/2] Pastikan Laragon sudah menyala (Klik "Start All").
echo [2/2] Pastikan Anda sudah memasukkan Authtoken ngrok.
echo.
echo Menjalankan tunnel ngrok ke port 80 (Apache Laragon)...
echo Setelah muncul link "Forwarding", tambahkan "/spkk/" di belakangnya:
echo Contoh: https://xxxx-xxx.ngrok-free.app/spkk/
echo.
echo --------------------------------------------------------------
echo Tekan Ctrl + C untuk berhenti online.
echo --------------------------------------------------------------
echo.

if exist "C:\laragon\bin\ngrok\ngrok.exe" (
    "C:\laragon\bin\ngrok\ngrok.exe" http 80
) else (
    echo [ERROR] ngrok.exe tidak ditemukan di C:\laragon\bin\ngrok\ngrok.exe
    pause
)

