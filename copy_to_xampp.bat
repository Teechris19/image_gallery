@echo off
echo Copying Image Gallery to XAMPP htdocs...
robocopy "C:\Users\USER\Documents\00-WEB DEV\real projects\image-gallery" "C:\xampp\htdocs\image-gallery" /E /NFL /NDL /XD .qwen
echo.
echo Copy complete!
echo.
echo You can now access the gallery at:
echo http://localhost/image-gallery/
echo.
pause
