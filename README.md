# Discord Scraper Bot Application

This application logs Discord messages from a specified channel into a MySQL database and displays them on a web page. It consists of a Python bot for Discord and a PHP web page for displaying the logged messages.

## Prerequisites

- **Discord Bot**: Create and configure a bot in the Discord Developer Portal.
- **MySQL Database**: Set up a MySQL database to store the logged messages.
- **Python**: Install Python and required libraries.
- **PHP Web Server**: Set up a web server capable of running PHP.

## Setup Instructions

### 1. Create a Discord Bot

1. Go to the [Discord Developer Portal](https://discord.com/developers/applications).
2. Click on "New Application".
3. Give your application a name and click "Create".
4. Navigate to the "Bot" tab and click "Add Bot".
5. Copy the bot token. You will need this for the `config.json`.

### 2. Set Up a MySQL Database

1. Install MySQL server on your machine or use a cloud-based MySQL service.
2. Create a new database and user.
3. Grant the user privileges to the database.
4. Create a table in your database with the following structure:

   ```sql
   CREATE TABLE discord_messages (
       id BIGINT PRIMARY KEY,
       timestamp DATETIME,
       author VARCHAR(255),
       content TEXT,
       channel_id BIGINT,
       server_id BIGINT
   );
   ```

### 3. Install Python and Required Libraries

1. Install Python from the official website: [Python Downloads](https://www.python.org/downloads/).
2. Install the required libraries by running:

   ```bash
   pip install discord.py python-dotenv mysql-connector-python
   ```

### 4. Set Up the `config.json` File

Create a `config.json` file in the root directory of your project with the following content:

```json
{
  "bot": {
    "token": "YOUR_DISCORD_TOKEN",
    "server_id": 123456789012345678,
    "channel_id": 987654321098765432
  },
  "database": {
    "enabled": true,
    "host": "your_db_host",
    "user": "your_db_user",
    "password": "your_db_password",
    "name": "your_db_name"
  },
  "logging": {
    "enabled": true,
    "log_channel_id": 567890123456789012,
    "log_bot_messages": true
  }
}
```

- Replace `YOUR_DISCORD_TOKEN` with the bot token you copied from the Discord Developer Portal.
- Replace `your_db_host`, `your_db_user`, `your_db_password`, and `your_db_name` with your MySQL database details.
- Replace `123456789012345678`, `987654321098765432`, and `567890123456789012` with your Discord server and channel IDs.

### 5. Run the Python Bot

Ensure the `bot.py` script is in your project directory. Start the bot by running:

```bash
python bot.py
```

The bot will connect to Discord, monitor the specified channel, and log messages to the MySQL database.

### 6. Set Up the PHP Web Page

Ensure the `index.php` file is in your web server's root directory.

1. Copy the `index.php` file to your PHP web server directory.
2. Ensure the web server has access to the `config.json` file to read the configuration.

### 7. View the Logged Messages

Open your web browser and navigate to the URL where your PHP web server is running. You should see the logged Discord messages displayed on the web page with search and filter functionality.

## Additional Information

- **Configuring Bot Logging**: You can enable or disable logging of bot messages by setting the `log_bot_messages` flag in the `config.json` file.
- **Pagination and Filtering**: The PHP web page supports pagination and filtering by content and timestamp.

## Disclaimer ⚠️⚠️⚠️

This is a learning project and not a professional-grade application. Feedback and contributions are appreciated.

## License

Licensed under GPL-3.0.