# ensure running as root
if [ "$(id -u)" != "0" ]; then
    #Debian doesnt have sudo if root has a password.
    if ! hash sudo 2>/dev/null; then
        exec su -c "$0" "$@"
    else
        exec sudo "$0" "$@"
    fi
fi

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "$SCRIPT_DIR/snipeit.sh" ]; then
    INSTALLER="$SCRIPT_DIR/snipeit.sh"
else
    INSTALLER="$(mktemp /tmp/snipeit-install.XXXXXX.sh)"
    wget -O "$INSTALLER" https://raw.githubusercontent.com/snipe/snipe-it/master/snipeit.sh
fi

chmod 744 "$INSTALLER"
"$INSTALLER" 2>&1 | tee -a /var/log/snipeit-install.log
