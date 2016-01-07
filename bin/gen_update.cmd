@echo off
:BEGIN
CLS
ECHO Enter version_index or Q for exit
IF "x%version_index%" == "x" (
	SET /p version_index=: 
) ELSE (
	SET /p version_index=default %version_index%: 
)
IF "%version_index%"=="Q" exit
IF "%version_index%"=="q" exit
D:\cygwin64\bin\bash.exe -c "/cygdrive/d/Projects/Z-TV/bin/gen_update.sh %version_index%"
PAUSE
GOTO:BEGIN