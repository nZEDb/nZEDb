# This patch will remove calendar from the menu items
# The decision was made that the calendar feature is best suited
# for third party tools that do it much better (Sonarr, Sickbeard, etc.)

DELETE FROM menu_items WHERE href = 'calendar';