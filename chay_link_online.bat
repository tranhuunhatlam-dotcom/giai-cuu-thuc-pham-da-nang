@echo off
title MO LINK ONLINE CHO BAN BE XEM WEB
cls
echo =========================================================================
echo   HE THONG GIAI CUU THUC PHAM DA NANG - TAO DUONG LINK ONLINE
echo =========================================================================
echo.
echo Dang ket noi duong truyen Internet Cloudflare Tunnel...
echo Khi man hinh hien ra dong co chua link:
echo    https://xxxxx.trycloudflare.com
echo.
echo Ban chi can copy link do va them duoi /giaicuu/ vao sau, vi du:
echo    https://xxxxx.trycloudflare.com/giaicuu/
echo roi gui cho ban be la mo duoc ngay tren dien thoai 4G bat ky!
echo.
echo [Luu y: Giu cua so nay mo khi muon ban be xem web]
echo =========================================================================
echo.
"C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --url http://localhost:80
pause
