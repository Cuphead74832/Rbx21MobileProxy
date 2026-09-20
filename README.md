## Rbx21MobileProxy
This tool allows you to send a valid joinscript to a 2021 mobile client to join any RCC, however let me tell ya. If you want mobile **your gonna have a bad time** (mobile works horrible)

Also this tool may work outside the 2021 era, but not beyond the 2021 era. 

- This guide will focus on how to replicate my enviroment.
- This guide is meant for android users, sorry iOS users!

**THIS PROJECT IS BRAND NEW AND IS STILL WORK IN PROGRESS, PLEASE EXCUSE THIS RUSHED README**
## Legal Notice
This tool DOES NOT WORK with the current version of the game or servers, you must provide your own. I am not responsible for misuse of this tool.

## Requirements
On PC:

- mitmproxy

- Python 3

- Git (optional)

On mobile:

- Termux with Python and git installed.

Network: 

- Both mobile and PC must be on the same network, or be able to talk to each other.

## Setup (on PC)
1. Clone this repository and cd into it.
```
git clone https://github.com/Cuphead74832/Rbx21MobileProxy
cd Rbx21MobileProxy
```
2. Patch your hosts file with the hosts in hosts.txt
- In Windows this is located at
```
C:\Windows\System32\drivers\etc\hosts
```
- On other systems, this is located at:
```
/etc/hosts
```
**WARNING: USE NOTEPAD++ FOR THE BEST RESULTS, ADDITIONALLY YOU WILL LOSE CONNECTION WITH THE OFFICIAL PLATFORM, DELETE THE LINES YOU ADDED TO YOUR HOSTS FILE TO RECONNECT BACK. THIS STEP MAY BE REMOVED IN THE FUTURE**

3. Install the requirements and start the python webserver
```
cd Webserver
pip install -r requirements.txt
py main.py -- On Windows run this
python3 main.py -- On Linux run this
```
4. Start mitmproxy with the -k flag (SSL insecure override) in another cmd window.
```
mitmproxy -k
```

5. Go to config.txt and setup the address to a valid asset server and other stuff like the username, this project does not use the official asset server, once changed restart your webserver.

Thats pretty much it for the PC part. 

## Setup (on Mobile)

1. Connect to the proxy and restart your network connection to ensure you are connected. If your on WiFi you may see a "no internet" notification, if you are using mobile data, you will need to change your APN settings to connect to the proxy.

2. Go to your desired browser and type "mitm.it" if your connected successfully, you should see a website served by the proxy itself. Download the android certificate and install it by going to your settings, each phone is different so i cant provide this step. Look up "How to install a CA cert on ..."

3. Install or patch your desired CLIENT and open it. (Pre-patched clients are on the releases)

4. **HERE COMES THE HARD PART** Open it and login with any credentials if asked (if not skip this step), then if you see the blue dot going left and right again instead of a homescreen, close it and delete the app data **(yes you heard that right, wipe the app data)** and continue to next step.

5. Open the rbxl website on your browser, you will see a blue "Go!" button, click it. If you see a grey "Joining server" screen followed by a failure to connect, the client has been setup successfully. Otherwise try logging in again and wiping the app data again and again and again, yeah this is the "bad time part".

6. Now open **Termux** and clone the repo then cd into it.
```
git clone https://github.com/Cuphead74832/Rbx21MobileProxy
cd Rbx21MobileProxy
```
7. Type the following:
```
python3 transparent_proxy.py 192.168.0.0:2005
```
With 192.168.0.0:2005 being replaced by the address of a working RCC server. (Having issues? Type "-v" next to it to put the proxy on verbose)

8. Thats it! Now just click that "Go!" button while keeping Termux in the background and you shud be in!

## Special Setups
If you are trying to connect to an instance of Freedom Distribution, ensure you have the following in your settings file:
```
[server_core]

allow_unsafe_users = true
```
This has been added by VisualPlugin himself to ensure mobile can connect! Thank you VisualPlugin!

## Backstory.
This project has been in the works for 9 months, after hard work, we bring you the ultimate way of using mobile clients without gatekeeping! I hope you enjoy, you can find my contacts on my GitHub Profile.

## How to patch yourself (DOESNT WORK IN LATEST)
1. Grab your client and execute apk-mitm on it with the "--wait" flag then access the directory of it once its saying "Waiting" then find libroblox.so

2. Open it with HxD or your preferred hex editor.

3. Look up "MIIBI"

4. Copy the contents from keys/rsa_public_2048.pub and replace all present public keys inside your client.

5. Patch SSL out of the client, since this is different PER client, i cannot help you here.

## TO DO

- [ ] Find a way to make emulated logins work when using the mitmproxy redirection script instead of a patched host file + not using 80 or 443 ports.
- [ ] Discard the PC from the setup for 100% portability
- [ ] Discover a way to grab assets despite hosts being patched as a temporary solution to the above until above is discovered.





