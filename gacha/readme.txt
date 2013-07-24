Usage:
$ php -d apc.enable_cli=1 run_gacha.php
(need php-apc though nothing will be from cache because of CLI.)

Files:
gacha_data_1.json
  gacha rating data.
gachaBase.php
  gacha base class. just draw with data file.
gachaHistoryConsideration.php
  draw gacha and the rate will be considered with history data.
run_gacha.php
  sample script to use gacha classes.

