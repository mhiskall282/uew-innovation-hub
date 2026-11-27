# Checks sign-in (and optionally sign-up) through the real web forms.
#   powershell -File scripts/signin-check.ps1 -Base http://127.0.0.1:8000 -Email admin@uew.edu.gh -Password <pw>
#   add -SignUp <address> to also create an account (sends a verification code e-mail)
param(
    [string]$Base = 'http://127.0.0.1:8000',
    [string]$Email,
    [string]$Password,
    [string]$SignUp
)
$jar = Join-Path $env:TEMP ("uew-jar-" + [guid]::NewGuid() + '.txt')
function Token { param($Path) $html = curl.exe -s -c $jar -b $jar --max-time 120 "$Base$Path"; [regex]::Match($html, 'name="_token" value="([^"]+)"').Groups[1].Value }
function Errors { param($Html) ([regex]::Matches($Html, 'text-red-[0-9]+[^>]*>([^<]{5,200})<') | ForEach-Object { $_.Groups[1].Value.Trim() } | Select-Object -Unique) -join ' | ' }

if ($Email) {
    $t = Token '/sign-in'
    $to = curl.exe -s -o NUL -c $jar -b $jar --max-time 90 -w '%{redirect_url}' --data-urlencode "_token=$t" --data-urlencode "email=$Email" --data-urlencode "password=$Password" "$Base/sign-in"
    if ($to -match '/sign-in$' -or -not $to) {
        $page = curl.exe -s -L -b $jar --max-time 90 "$Base/sign-in"
        "SIGN-IN FAILED for $Email : $(Errors $page)"
    } else {
        $landing = curl.exe -s -o NUL -b $jar --max-time 90 -w '%{http_code}' $to
        "sign-in OK for $Email -> $to ($landing)"
        $t2 = [regex]::Match((curl.exe -s -b $jar --max-time 90 $to), 'name="_token" value="([^"]+)"').Groups[1].Value
        $out = curl.exe -s -o NUL -c $jar -b $jar --max-time 90 -w '%{redirect_url}' --data-urlencode "_token=$t2" "$Base/sign-out"
        $after = curl.exe -s -o NUL -b $jar --max-time 90 -w '%{redirect_url}' "$to"
        "sign-out -> $out ; afterwards $to redirects to $after"
    }
}

if ($SignUp) {
    Remove-Item $jar -ErrorAction SilentlyContinue
    $t = Token '/sign-up'
    $to = curl.exe -s -o NUL -c $jar -b $jar --max-time 120 -w '%{redirect_url}' `
        --data-urlencode "_token=$t" --data-urlencode 'name=Web Signup Test' --data-urlencode "email=$SignUp" `
        --data-urlencode 'phone=+233 24 000 1111' --data-urlencode 'gender=prefer_not' --data-urlencode 'date_of_birth=2000-01-01' `
        --data-urlencode 'region=Central' --data-urlencode 'password=Web-Signup-2026x' --data-urlencode 'password_confirmation=Web-Signup-2026x' `
        --data-urlencode 'terms=1' "$Base/sign-up"
    if ($to -match '/verify-email$') { "sign-up OK for $SignUp -> $to (a 6-digit code e-mail was sent)" }
    else { "SIGN-UP FAILED for $SignUp -> $to : $(Errors (curl.exe -s -L -b $jar --max-time 90 "$Base/sign-up"))" }
}
Remove-Item $jar -ErrorAction SilentlyContinue
