<?php
namespace Froxlor\Cron\Http;

use Froxlor\Database\Database;
use Froxlor\Settings;
use Froxlor\Cron\Http\Php\Fpm;

/**
 * This file is part of the Froxlor project.
 * Copyright (c) 2016 the Froxlor Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.froxlor.org/misc/COPYING.txt
 *
 * @copyright (c) the authors
 * @author Froxlor team <team@froxlor.org> (2016-)
 * @license GPLv2 http://files.froxlor.org/misc/COPYING.txt
 * @package Cron
 *         
 */

/**
 * Class HttpConfigBase
 *
 * Base class for all HTTP server configs
 */
class HttpConfigBase
{

	public function init()
	{
		// if Let's Encrypt is activated, run it before regeneration of webserver configfiles
		if (Settings::Get('system.leenabled') == 1) {
			\Froxlor\FroxlorLogger::getInstanceOf()->logAction(\Froxlor\FroxlorLogger::CRON_ACTION, LOG_INFO, 'Running Let\'s Encrypt cronjob prior to regenerating webserver config files');
			\Froxlor\Cron\Http\LetsEncrypt\AcmeSh::$no_inserttask = true;
			\Froxlor\Cron\Http\LetsEncrypt\AcmeSh::run(true);
			// set last run timestamp of cronjob
			\Froxlor\System\Cronjob::updateLastRunOfCron('letsencrypt');
		}
	}

	public function reload()
	{
		$called_class = get_called_class();
		if ((int) Settings::Get('phpfpm.enabled') == 1) {
			// get all start/stop commands
			$startstop_sel = Database::prepare("SELECT reload_cmd, config_dir FROM `" . TABLE_PANEL_FPMDAEMONS . "`");
			Database::pexecute($startstop_sel);
			$restart_cmds = $startstop_sel->fetchAll(\PDO::FETCH_ASSOC);
			// restart all php-fpm instances
			foreach ($restart_cmds as $restart_cmd) {
				// check whether the config dir is empty (no domains uses this daemon)
				// so we need to create a dummy
				$_conffiles = glob(\Froxlor\FileDir::makeCorrectFile($restart_cmd['config_dir'] . "/*.conf"));
				if ($_conffiles === false || empty($_conffiles)) {
					\Froxlor\FroxlorLogger::getInstanceOf()->logAction(\Froxlor\FroxlorLogger::CRON_ACTION, LOG_INFO, $called_class . '::reload: fpm config directory "' . $restart_cmd['config_dir'] . '" is empty. Creating dummy.');
					Fpm::createDummyPool($restart_cmd['config_dir']);
				}
				\Froxlor\FroxlorLogger::getInstanceOf()->logAction(\Froxlor\FroxlorLogger::CRON_ACTION, LOG_INFO, $called_class . '::reload: running ' . $restart_cmd['reload_cmd']);
				\Froxlor\FileDir::safe_exec(escapeshellcmd($restart_cmd['reload_cmd']));
			}
		}
		\Froxlor\FroxlorLogger::getInstanceOf()->logAction(\Froxlor\FroxlorLogger::CRON_ACTION, LOG_INFO, $called_class . '::reload: reloading ' . $called_class);
		\Froxlor\FileDir::safe_exec(escapeshellcmd(Settings::Get('system.apachereload_command')));

		/**
		 * nginx does not auto-spawn fcgi-processes
		 */
		if (Settings::Get('system.webserver') == "nginx" && Settings::Get('system.phpreload_command') != '' && (int) Settings::Get('phpfpm.enabled') == 0) {
			\Froxlor\FroxlorLogger::getInstanceOf()->logAction(\Froxlor\FroxlorLogger::CRON_ACTION, LOG_INFO, $called_class . '::reload: restarting php processes');
			\Froxlor\FileDir::safe_exec(Settings::Get('system.phpreload_command'));
		}
	}

	/**
	 * process special config as template, by substituting {VARIABLE} with the
	 * respective value.
	 *
	 * The following variables are known at the moment:
	 *
	 * {DOMAIN} - domain name
	 * {IP} - IP for this domain
	 * {PORT} - Port for this domain
	 * {CUSTOMER} - customer name
	 * {IS_SSL} - evaluates to 'ssl' if domain/ip is ssl, otherwise it is an empty string
	 * {DOCROOT} - document root for this domain
	 *
	 * @param
	 *        	$template
	 * @return string
	 */
	protected function processSpecialConfigTemplate($template, $domain, $ip, $port, $is_ssl_vhost)
	{
		$templateVars = array(
			'DOMAIN' => $domain['domain'],
			'CUSTOMER' => $domain['loginname'],
			'IP' => $ip,
			'PORT' => $port,
			'SCHEME' => ($is_ssl_vhost) ? 'https' : 'http',
			'DOCROOT' => $domain['documentroot']
		);
		return \Froxlor\PhpHelper::replaceVariables($template, $templateVars);
	}

	protected function getMyPath($ip_port = null)
	{
		if (! empty($ip_port) && $ip_port['docroot'] == '') {
			if (Settings::Get('system.froxlordirectlyviahostname')) {
				$mypath = \Froxlor\FileDir::makeCorrectDir(\Froxlor\Froxlor::getInstallDir());
			} else {
				$mypath = \Froxlor\FileDir::makeCorrectDir(dirname(\Froxlor\Froxlor::getInstallDir()));
			}
		} else {
			// user-defined docroot, #417
			$mypath = \Froxlor\FileDir::makeCorrectDir($ip_port['docroot']);
		}
		return $mypath;
	}

	protected function checkAlternativeSslPort()
	{
		// We must not check if our port differs from port 443,
		// but if there is a destination-port != 443
		$_sslport = '';
		// This returns the first port that is != 443 with ssl enabled,
		// ordered by ssl-certificate (if any) so that the ip/port combo
		// with certificate is used
		$ssldestport_stmt = Database::prepare("
			SELECT `ip`.`port` FROM " . TABLE_PANEL_IPSANDPORTS . " `ip`
			WHERE `ip`.`ssl` = '1'  AND `ip`.`port` != 443
			ORDER BY `ip`.`ssl_cert_file` DESC, `ip`.`port` LIMIT 1;
		");
		$ssldestport = Database::pexecute_first($ssldestport_stmt);

		if ($ssldestport['port'] != '') {
			$_sslport = ":" . $ssldestport['port'];
		}

		return $_sslport;
	}

	protected function froxlorVhostHasLetsEncryptCert()
	{
		// check whether we have an entry with valid certificates which just does not need
		// updating yet, so we need to skip this here
		$froxlor_ssl_settings_stmt = Database::prepare("
			SELECT * FROM `" . TABLE_PANEL_DOMAIN_SSL_SETTINGS . "` WHERE `domainid` = '0'
		");
		$froxlor_ssl = Database::pexecute_first($froxlor_ssl_settings_stmt);
		if ($froxlor_ssl && ! empty($froxlor_ssl['ssl_cert_file'])) {
			return true;
		}
		return false;
	}

	protected function froxlorVhostLetsEncryptNeedsRenew()
	{
		$froxlor_ssl_settings_stmt = Database::prepare("
			SELECT * FROM `" . TABLE_PANEL_DOMAIN_SSL_SETTINGS . "`
			WHERE `domainid` = '0' AND
			(`expirationdate` < DATE_ADD(NOW(), INTERVAL 30 DAY) OR `expirationdate` IS NULL)
		");
		$froxlor_ssl = Database::pexecute_first($froxlor_ssl_settings_stmt);
		if ($froxlor_ssl && ! empty($froxlor_ssl['ssl_cert_file'])) {
			return true;
		}
		return false;
	}
}
