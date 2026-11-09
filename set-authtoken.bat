@echo off
title Setup Authtoken ngrok - SPKK
color 0a
echo ==============================================================
echo   SETUP AUTHTOKEN NGROK - SPKK HIPERTENSI
echo ==============================================================
echo.
echo Dapatkan token gratis Anda di:
echo https://dashboard.ngrok.com/get-started/your-authtoken
echo.
set /p TOKEN="Paste (tempelkan) authtoken Anda di sini lalu tekan Enter: "
echo.
if "%TOKEN%"=="" (
    echo [ERROR] Token tidak boleh kosong!
    pause
    exit /b
)

if exist "C:\laragon\bin\ngrok\ngrok.exe" (
    "C:\laragon\bin\ngrok\ngrok.exe" config add-authtoken %TOKEN%
    echo.
    echo ==============================================================
    echo [SUKSES] Token berhasil disimpan!
    echo Selanjutnya, Anda tinggal klik 2x file: jalankan-ngrok.bat
    echo ==============================================================
) else (
    echo [ERROR] ngrok.exe tidak ditemukan di C:\laragon\bin\ngrok\ngrok.exe
)
pause

