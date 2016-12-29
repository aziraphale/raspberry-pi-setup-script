#!/usr/bin/env bash

# Ensure that PHP is installed
which php >/dev/null || {
    echo "PHP is not currently installed."
    echo "Do you want to install the 'php5-cli' package and continue?"
    warnNoPhpResult=""
    while [[ ! $warnNoPhpResult =~ ^[YN]$ ]]; do
        # Wait until we have a Y/N answer...
        read -p "Install and continue? [Y/N] " warnNoPhpResult
    done

    if [ "$warnNoPhpResult" == "N" ]; then
        echo "Unable to continue without PHP installed. Exiting."
        exit 1
    else
        echo "Attempting to install 'php-cli'... You may receive a 'sudo' password prompt."
        sudo apt-get -y install php5-cli

        which php >/dev/null || {
            echo "PHP still doesn't appear to be installed. Please install it manually (probably via the 'php5-cli' package, or similar, so that a 'php' command is in the PATH) and then re-run this script."
            exit 2
        }
    fi
}

# PHP is installed (either it was always installed, or it's NOW installed thanks to the above code) so we can fetch and execute our PHAR archive...
echo "The main setup script and sample config file will now be downloaded from Hex over SFTP."
echo "YOU WILL BE PROMPTED FOR YOUR SSH PASSWORD TO HEX:"
scp andrew@hex.lorddeath.net:/mnt/backups/RPi/_setup/raspi-setup{.phar.gz,config-sample.json} . || {
    echo "Failed to download required files from Hex. Please investigate the problem and try again."
    exit 3
}

echo "Do you want to edit and use a configuration file? This is recommended, as it results in faster and more unattended-suitable installations, but may not be suitable if you are unfamiliar with the installer."
useConfigResult=""
while [[ ! $useConfigResult =~ ^[YN]$ ]]; do
    # Wait until we have a Y/N answer...
    read -p "Install and continue? [Y/N] " useConfigResult
done
if [ "$useConfigResult" == "N" ]; then
    echo "Not using configuration file."
    rm ./raspi-setup-config-sample.json
    php raspi-setup.phar.gz && rm raspi-setup.phar.gz
else
    echo "Using configuration file."
    read -p "Press ENTER to edit the configuration file and continue..." dummy
    vim ./raspi-setup-config-sample.json
    php raspi-setup.phar.gz --config=raspi-setup-config-sample.json && rm raspi-setup.phar.gz raspi-setup-config-sample.json
fi


