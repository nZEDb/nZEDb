<?php
/**
 * Executes multiple queries in a 'bulk' to achieve better
 * performance and integrity.
 *
 * @param array  $data	An array of queries. Except for loaddata methods. Those require a 2 dimensional array.
 * @param string $table
 * @param string $method
 * @param array  $options
 *
 * found here, by Kevin van Zonneveld 
 * http://kvz.io/blog/2009/03/31/improve-mysql-insert-performance/
 * @return float
 */
function mysqlBulk(&$data, $table, $method = 'transaction', $options = array()) {
	// Default options
	if (!isset($options['query_handler'])) {
		$options['query_handler'] = 'mysqli_query';
	}
	if (!isset($options['error_handler'])) {
		$options['error_handler'] = 'mysqli_error';
	}
	if (!isset($options['trigger_errors'])) {
		$options['trigger_errors'] = true;
	}
	if (!isset($options['trigger_notices'])) {
		$options['trigger_notices'] = true;
	}
	if (!isset($options['eat_away'])) {
		$options['eat_away'] = false;
	}
	if (!isset($options['in_file'])) {
		// AppArmor may prevent MySQL to read this file.
		// Remember to check /etc/apparmor.d/usr.sbin.mysqld
		$options['in_file'] = '/dev/shm/infile.txt';
	}
	if (!isset($options['link_identifier'])) {
		$options['link_identifier'] = null;
	}

	// Make options local
	extract($options);

	// Validation
	if (!is_array($data)) {
		if ($trigger_errors) {
			trigger_error('First argument "queries" must be an array',
				E_USER_ERROR);
		}
		return false;
	}
	if (empty($table)) {
		if ($trigger_errors) {
			trigger_error('No insert table specified',
				E_USER_ERROR);
		}
		return false;
	}
	if (count($data) > 10000) {
		if ($trigger_notices) {
			trigger_error('It\'s recommended to use <= 10000 queries/bulk',
				E_USER_NOTICE);
		}
	}
	if (empty($data)) {
		return 0;
	}

	if (!function_exists('__exe')) {
		function __exe ($sql, $query_handler, $error_handler, $trigger_errors, $link_identifier = null) {
			if ($link_identifier === null) {
				$x = call_user_func($query_handler, $sql);
			} else {
				$x = call_user_func($query_handler, $sql, $link_identifier);
			}
			if (!$x) {
				if ($trigger_errors) {
					$error_msg = call_user_func($error_handler);
					trigger_error(sprintf(
						'Query failed. %s [sql: %s]',
						$error_msg,
						$sql
					), E_USER_ERROR);
					return false;
				}
			}

			return true;
		}
	}

	if (!function_exists('__sql2array')) {
		function __sql2array($sql, $trigger_errors) {
			if (substr(strtoupper(trim($sql)), 0, 6) !== 'INSERT') {
				if ($trigger_errors) {
					trigger_error('Magic sql2array conversion '.
						'only works for inserts',
						E_USER_ERROR);
				}
				return false;
			}

			$parts   = preg_split("/[,\(\)] ?(?=([^'|^\\\']*['|\\\']" .
								  "[^'|^\\\']*['|\\\'])*[^'|^\\\']" .
								  "*[^'|^\\\']$)/", $sql);
			$process = 'keys';
			$dat     = array();

			foreach ($parts as $k=>$part) {
				$tpart = strtoupper(trim($part));
				if (substr($tpart, 0, 6) === 'INSERT') {
					continue;
				} else if (substr($tpart, 0, 6) === 'VALUES') {
					$process = 'values';
					continue;
				} else if (substr($tpart, 0, 1) === ';') {
					continue;
				}

				if (!isset($data[$process])) $data[$process] = array();
				$data[$process][] = $part;
			}

			return array_combine($data['keys'], $data['values']);
		}
	}

	// Start timer
	$start = microtime(true);
	$count = count($data);

	// Choose bulk method
	switch ($method) {
		case 'loaddata':
		case 'loaddata_unsafe':
		case 'loadsql_unsafe':
			// Inserts data only
			// Use array instead of queries

			$buf = '';
			foreach ($data as $i => $row) {
				if ($method === 'loadsql_unsafe') {
					$row = __sql2array($row, $trigger_errors);
				}
				$buf .= join(':::,', $row) . "^^^\n";
			}

			$fields = join(', ', array_keys($row));

			if (!@file_put_contents($in_file, $buf)) {
				$trigger_errors && trigger_error('Cant write to buffer file: "' . $in_file . '"', E_USER_ERROR);
				return false;
			}

			if ($method === 'loaddata_unsafe') {
				if (!__exe("SET UNIQUE_CHECKS=0", $query_handler, $error_handler, $trigger_errors, $link_identifier)) return false;
				if (!__exe("set foreign_key_checks=0", $query_handler, $error_handler, $trigger_errors, $link_identifier)) return false;
				// Only works for SUPER users:
				#if (!__exe("set sql_log_bin=0", $query_handler, $error_handler, $trigger_error)) return false;
				if (!__exe("set unique_checks=0", $query_handler, $error_handler, $trigger_errors, $link_identifier)) return false;
			}

			if (!__exe("
				LOAD DATA INFILE '${in_file}'
				INTO TABLE ${table}
				FIELDS TERMINATED BY ':::,'
				LINES TERMINATED BY '^^^\\n'
				(${fields})
			", $query_handler, $error_handler, $trigger_errors, $link_identifier)) return false;

			break;
		case 'transaction':
		case 'transaction_lock':
		case 'transaction_nokeys':
			// Max 26% gain, but good for data integrity
			if ($method == 'transaction_lock') {
				if (!__exe('SET autocommit = 0', $query_handler, $error_handler, $trigger_errors, $link_identifier)) return false;
				if (!__exe('LOCK TABLES '.$table.' READ', $query_handler, $error_handler, $trigger_errors, $link_identifier)) return false;
			} else if ($method == 'transaction_keys') {
				if (!__exe('ALTER TABLE '.$table.' DISABLE KEYS', $query_handler, $error_handler, $trigger_errors, $link_identifier)) return false;
			}

			if (!__exe('START TRANSACTION', $query_handler, $error_handler, $trigger_errors, $link_identifier)) return false;

			foreach ($data as $query) {
				if (!__exe($query, $query_handler, $error_handler, $trigger_errors, $link_identifier)) {
					__exe('ROLLBACK', $query_handler, $error_handler, $trigger_errors, $link_identifier);
					if ($method == 'transaction_lock') {
						__exe('UNLOCK TABLES '.$table.'', $query_handler, $error_handler, $trigger_errors, $link_identifier);
					}
					return false;
				}
			}

			__exe('COMMIT', $query_handler, $error_handler, $trigger_errors, $link_identifier);

			if ($method == 'transaction_lock') {
				if (!__exe('UNLOCK TABLES', $query_handler, $error_handler, $trigger_errors, $link_identifier)) return false;
			} else if ($method == 'transaction_keys') {
				if (!__exe('ALTER TABLE '.$table.' ENABLE KEYS', $query_handler, $error_handler, $trigger_errors, $link_identifier)) return false;
			}
			break;
		case 'none':
			foreach ($data as $query) {
				if (!__exe($query, $query_handler, $error_handler, $trigger_errors, $link_identifier)) return false;
			}

			break;
		case 'delayed':
			// MyISAM, MEMORY, ARCHIVE, and BLACKHOLE tables only!
			if ($trigger_errors) {
				trigger_error('Not yet implemented: "'.$method.'"',
					E_USER_ERROR);
			}
			break;
		case 'concatenation':
		case 'concat_trans':
			// Unknown bulk method
			if ($trigger_errors) {
				trigger_error('Deprecated bulk method: "'.$method.'"',
					E_USER_ERROR);
			}
			return false;
			break;
		default:
			// Unknown bulk method
			if ($trigger_errors) {
				trigger_error('Unknown bulk method: "'.$method.'"',
					E_USER_ERROR);
			}
			return false;
			break;
	}

	// Stop timer
	$duration = microtime(true) - $start;
	$qps	  = round ($count / $duration, 2);

	if ($eat_away) {
		$data = array();
	}

	@unlink($options['in_file']);

	// Return queries per second
	return $qps;
}
