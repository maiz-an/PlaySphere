# PlaySphere Setup Guide

Welcome to the PlaySphere setup guide! This document will help you through the steps to set up and configure PlaySphere on your local machine.

---
## 🔑 Admin Configuration

To configure your **PlaySphere** admin, set the **username** and **password**. Please ensure that the password is **strong** and kept **secure**.

### Admin Credentials

- **Admin Username:** `playsphere`  
- **Admin Password:** `@Admin123`  

> 💡 **Note:**  
> Replace `playsphere` and `Admin123` with your actual values. Be sure to **store these securely**!

---

## 🛠️ Prerequisites

Before getting started, make sure you have the following:

- A local web server environment (e.g., **WAMP**, **XAMPP**)
- PHP with **curl** and **openssl** extensions enabled
- A mail service account (e.g., **Gmail**) to send emails

---

## 📝 Setup Steps

### 1. Extract the PlaySphere Files
Unzip the PlaySphere files and place them in your local web server directory. For example, if you're using WAMP, place the files in `C:\wamp64\www\playSphere`.

### 2. Add cacert.pem to your PHP directory eg: C:\wamp64\bin\php (Directry the file on cacert directry)
To ensure proper SSL verification, include the cacert.pem file in your PHP installation directory. For example:
Place the cacert.pem file inside the php directory (e.g., C:\wamp64\bin\php).

Your path should look like this:
C:\wamp64\bin\php\cacert.pem

### 3. Configure `ini.php`
Open the `ini.php` file and set the following two directives for `curl` and `openssl` to ensure proper functionality:

```ini
[curl]
; A default value for the CURLOPT_CAINFO option. This is required to be an
; absolute path.
curl.cainfo = "C:\wamp64\bin\php\cacert.pem"

[openssl]
; The location of a Certificate Authority (CA) file on the local filesystem
; to use when verifying the identity of SSL/TLS peers. Most users should
; not specify a value for this directive as PHP will attempt to use the
; OS-managed cert stores in its absence. If specified, this value may still
; be overridden on a per-stream basis via the "cafile" SSL stream context
; option.
openssl.cafile = "C:\wamp64\bin\php\cacert.pem"
---

```
### 4. Set Up Your Email Configuration

You can either use an **existing email account** or create a **new one** specifically for sending emails.

## 🔑 How to Create an App Password (for Gmail):
1. Go to [Google Account Security Settings](https://myaccount.google.com/security).
2. Under **"Signing in to Google,"** enable **2-Step Verification** if not already enabled. 
    - 🔐 This step adds an extra layer of security to your account.
3. Click on **"App passwords."**
    - 🖥️ Select **"Other (Custom name)"** and enter something like **PlaySphere Mail** to identify this password later.
4. Select the app and device for which you want to generate the password.
    - 📱 Select **Mail** and **Windows Computer** or your preferred device.
5. Copy the generated password and use it in your PlaySphere configuration.

---

### 5. Testing the Setup

Once you’ve completed the configuration, **test the application** by running it on your local server. 

- ✅ **Make sure emails are being sent correctly** by verifying the email functionality within PlaySphere.
    - 📧 Check the inbox of the email address you're sending from, or the recipient email, to ensure the message was received.

---

## ⚠️ Troubleshooting

If you encounter any issues during setup or configuration, feel free to reach out for assistance:

📧 **Email:** [mohamedmaizanmunas@gmail.com](mailto:mohamedmaizanmunas@gmail.com)

---

💡 **Pro Tip:** If you’re facing SSL/TLS certificate errors, ensure that your `cacert.pem` file is in the correct location and referenced properly in the `ini.php` file.

---


