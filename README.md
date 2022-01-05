# Widget for Invisioncommunity and Matrix.org

This repository contains the widget for the Invisioncommunity forum 
software to show online chat users in a Matrix.org Chat.

The Widget was tested with 
 - Invisioncommunity 4.6.9
 - PHP 7.4
 - Synapse 1.49.2

When adding the Widget to the Forum, use the edit button to specify:
 - Url: the matrix server url (example: ```https://matrix.tolkienforum.de```)
 - Token: the token of a user to be used for the REST API requests.
   The token can be retrieved in element-web in the settings, help and about section.
 - Room-Id: the room-id to read presence information for and the topic.
   NOTE: this is the Id - not the Room Name - it will start with a ```!```
   the room-id for a room is shown in the settings for that room (using element-web)
 - List of Forum-Usernames to not show presence information (usually the one you have the token from)

