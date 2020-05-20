<?php

/**
 * Generate test data in MySQL database tables for chess module.
 *
 * This script is designed to be run from the command line, not from a web browser.
 *
 * @package chess
 * @subpackage test
 */
error_reporting(E_ALL);

/**#@+
 */
define('DBHOST', 'localhost');
define('DBNAME', 'test');
define('DBUSER', 'root');
define('DBPASS', '');

define('NUM_USERS', 3);
define('NUM_CHALLENGES', 1000);
define('NUM_GAMES', 10000);
define('NUM_RATINGS', NUM_USERS / 2);
/**#@-*/

perform();

/**
 * Generate the test data.
 */
function perform()
{
    $challenges_table = 'chess_challenges';

    $games_table = 'chess_games';

    $ratings_table = 'chess_ratings';

    @mysql_connect(DBHOST, DBUSER, DBPASS) or trigger_error('[' . $GLOBALS['xoopsDB']->errno() . '] ' . $GLOBALS['xoopsDB']->error(), E_USER_ERROR);

    mysqli_select_db($GLOBALS['xoopsDB']->conn, DBNAME) or trigger_error('[' . $GLOBALS['xoopsDB']->errno() . '] ' . $GLOBALS['xoopsDB']->error(), E_USER_ERROR);

    // For safety, don't generate test data unless the tables are empty.

    if (!table_empty($challenges_table) || !table_empty($games_table) || !table_empty($ratings_table)) {
        echo "Tables already contain data - no action performed.\n";

        exit;
    }

    // Generate the challenges table

    $game_types = ['open', 'user'];

    $color_options = ['player2', 'random', 'white', 'black'];

    for ($i = 0; $i < NUM_CHALLENGES; ++$i) {
        $game_type = rand_array_value($game_types);

        $fen_index = mt_rand(1, 10);

        $fen = 10 == $fen_index ? 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' : '';

        $color_option = rand_array_value($color_options);

        $notify_move_player1 = mt_rand(0, 1);

        $player1_uid = mt_rand(1, NUM_USERS);

        if ('open' == $game_type) {
            $player2_uid = 0;
        } else {
            // select $player2_uid != $player1_uid

            do {
                $player2_uid = mt_rand(1, NUM_USERS);
            } while ($player2_uid == $player1_uid);
        }

        $create_date_max = time();

        $create_date_min = $create_date_max - 30 * 24 * 3600;

        $create_date = date('Y-m-d H:i:s', mt_rand($create_date_min, $create_date_max));

        $is_rated = mt_rand(0, 1);

        do_query("
			INSERT INTO $challenges_table
			SET
				game_type           = '$game_type',
				fen                 = '$fen',
				color_option        = '$color_option',
				notify_move_player1 = '$notify_move_player1',
				player1_uid         = '$player1_uid',
				player2_uid         = '$player2_uid',
				create_date         = '$create_date',
				is_rated            = '$is_rated'
		");
    }

    // Generate the games table

    $pgn_results = ['*', '0-1', '1-0', '1/2-1/2'];

    $suspended_explains = ['foo', 'bar', 'baz', 'quux'];

    for ($i = 0; $i < NUM_GAMES; ++$i) {
        $white_uid = mt_rand(1, NUM_USERS);

        $black_uid = mt_rand(1, NUM_USERS);

        // Force some games to be self-play.

        if (10 == mt_rand(1, 10)) {
            $black_uid = $white_uid;
        }

        $create_date_max = time();

        $create_date_min = $create_date_max - 365 * 24 * 3600;

        $create_date_sec = mt_rand($create_date_min, $create_date_max);

        $create_date = date('Y-m-d H:i:s', $create_date_sec);

        $is_started = mt_rand(1, 4) < 4;

        $start_date_sec = $is_started ? $create_date_sec + mt_rand(3600, 3 * 24 * 3600) : 0;

        $start_date = $is_started ? date('Y-m-d H:i:s', $start_date_sec) : '0000-00-00 00:00:00';

        $multiple_moves = $is_started && mt_rand(1, 10) < 10;

        $last_date_sec = $multiple_moves ? $start_date_sec + mt_rand(3600, 90 * 24 * 3600) : 0;

        $last_date = $multiple_moves ? date('Y-m-d H:i:s', $last_date_sec) : '0000-00-00 00:00:00';

        $pgn_result = $multiple_moves ? rand_array_value($pgn_results) : '*';

        if ($multiple_moves && '*' == $pgn_result && 5 == mt_rand(1, 5)) {
            $suspended_date = date('Y-m-d H:i:s', $last_date_sec + mt_rand(60, 72 * 3600));

            $suspended_uids = [1, $white_uid, $black_uid];

            $suspended_uid = rand_array_value($suspended_uids);

            $suspended_type = 1 == $suspended_uid ? 'arbiter_suspend' : 'want_arbitration';

            $suspended_explain = rand_array_value($suspended_explains);

            $suspended = "$suspended_date|$suspended_uid|$suspended_type|$suspended_explain";
        } else {
            $suspended = '';
        }

        $is_rated = $white_uid != $black_uid ? mt_rand(0, 1) : 0;

        do_query("
			INSERT INTO $games_table
			SET
				white_uid   = '$white_uid',
				black_uid   = '$black_uid',
				create_date = '$create_date',
				start_date  = '$start_date',
				last_date   = '$last_date',
				pgn_result  = '$pgn_result',
				suspended   = '$suspended',
				is_rated    = '$is_rated'
		");
    }

    $GLOBALS['xoopsDB']->close();
}

/**
 * Check whether table is empty.
 *
 * @param string $table Table name
 * @return bool True if table is empty
 */
function table_empty($table)
{
    $result = do_query("SELECT COUNT(*) FROM $table");

    [$num_rows] = $GLOBALS['xoopsDB']->fetchRow($result);

    $GLOBALS['xoopsDB']->freeRecordSet($result);

    return 0 == $num_rows;
}

/**
 * Perform MySQL query.
 *
 * If the result from $GLOBALS['xoopsDB']->queryF() is false, trigger_error() is called to display the error.
 *
 * @param string $query The query to perform
 * @return bool Return from $GLOBALS['xoopsDB']->queryF()
 */
function do_query($query)
{
    $result = $GLOBALS['xoopsDB']->queryF($query);

    if (false === $result) {
        $errno = $GLOBALS['xoopsDB']->errno();

        $error = $GLOBALS['xoopsDB']->error();

        trigger_error("[$errno] $error\n$query", E_USER_ERROR);
    }

    return $result;
}

 /**
  * @param $array
  * @return mixed
  */
 function rand_array_value($array)
 {
     return $array[array_rand($array)];
 }
