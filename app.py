import discord
import logging
import mysql.connector
import asyncio
import json
from datetime import datetime

# Logging for errors
logging.basicConfig(level=logging.INFO)

# Load config
with open('config.json') as config_file:
    config = json.load(config_file)

# Define the parameters from the config
TOKEN = config['bot']['token']
SERVER_ID = config['bot']['server_id']
CHANNEL_ID = config['bot']['channel_id']

DB_CONFIG = config['database']
LOGGING_CONFIG = config['logging']

# Create the Discord bot class
class DiscordScraperBot(discord.Client):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, intents=intents, **kwargs)

        # Set up MySQL connection if true
        if DB_CONFIG['enabled']:
            self.db_conn = mysql.connector.connect(
                host=DB_CONFIG['host'],
                user=DB_CONFIG['user'],
                password=DB_CONFIG['password'],
                database=DB_CONFIG['name']
            )
            self.db_cursor = self.db_conn.cursor()

    async def on_ready(self):
        print(f'Logged in as {self.user}')

    async def on_message(self, message):
        if message.channel.id != CHANNEL_ID:
            return  # Only log messages from the specified channel

        if message.author.bot and not LOGGING_CONFIG.get('log_bot_messages', False):
            return  # Skip logging bot messages if not enabled in config

        try:
            # Get message details
            timestamp = message.created_at.strftime('%Y-%m-%d %H:%M:%S')
            author = str(message.author)
            content = message.content.replace('\n', ' ')  # Avoid newlines in the database

            # Log the message to console
            print(f"{timestamp} - {author}: {content}")

            # Insert the message into the MySQL database if true
            if DB_CONFIG['enabled']:
                insert_query = "INSERT INTO discord_messages (id, timestamp, author, content, channel_id, server_id) VALUES (%s, %s, %s, %s, %s, %s)"
                self.db_cursor.execute(insert_query, (message.id, timestamp, author, content, CHANNEL_ID, SERVER_ID))
                self.db_conn.commit()

            # Log the message to the specified logging channel if true
            if LOGGING_CONFIG['enabled']:
                log_channel = self.get_channel(LOGGING_CONFIG['log_channel_id'])
                if log_channel:
                    await log_channel.send(f"{timestamp} - {author}: {content}")

            # Avoid rate limits
            await asyncio.sleep(1)
        except Exception as e:
            logging.error(f"Error logging message: {e}")

    async def on_disconnect(self):
        try:
            # Close the MySQL connection if true when the bot disconnects
            if DB_CONFIG['enabled']:
                self.db_cursor.close()
                self.db_conn.close()
        except Exception as e:
            logging.error(f"Error during disconnection: {e}")

# Run the bot 
intents = discord.Intents.default()
intents.messages = True
intents.guilds = True
intents.message_content = True

bot = DiscordScraperBot()
bot.run(TOKEN)
