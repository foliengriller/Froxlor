<?php

/**
 * This file is part of the Froxlor project.
 * Copyright (c) 2003-2009 the SysCP Team (see authors).
 * Copyright (c) 2010 the Froxlor Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.froxlor.org/misc/COPYING.txt
 *
 * @copyright  (c) the authors
 * @author     Florian Lippert <flo@syscp.org> (2003-2009)
 * @author     Froxlor team <team@froxlor.org> (2010-)
 * @license    GPLv2 http://files.froxlor.org/misc/COPYING.txt
 * @package    Configfiles
 * @version    $Id$
 */

return Array(
	'gentoo' => Array(
		'label' => 'Gentoo',
		'services' => Array(
			'http' => Array(
				'label' => $lng['admin']['configfiles']['http'],
				'daemons' => Array(
					'apache2' => Array(
						'label' => 'Apache2 Webserver',
						'commands' => Array(
							'touch ' . $settings['system']['apacheconf_vhost'],
							'chown root:0 ' . $settings['system']['apacheconf_vhost'],
							'chmod 0600 ' . $settings['system']['apacheconf_vhost'],
							'touch ' . $settings['system']['apacheconf_diroptions'],
							'chown root:0 ' . $settings['system']['apacheconf_diroptions'],
							'chmod 0600 ' . $settings['system']['apacheconf_diroptions'],
							'mkdir -p ' . $settings['system']['documentroot_prefix'],
							($settings['system']['deactivateddocroot'] != '') ? 'mkdir -p ' . $settings['system']['deactivateddocroot'] : '',
							'mkdir -p ' . $settings['system']['logfiles_directory'],
							'mkdir -p ' . $settings['system']['mod_fcgid_tmpdir'],
							'chmod 1777 ' . $settings['system']['mod_fcgid_tmpdir']
						),
						'restart' => Array(
							'rc-update add apache2 default',
							'/etc/init.d/apache2 restart'
						)
					),
					'lighttpd' => Array(
						'label' => 'Lighttpd Webserver',
						'files' => Array(
							'etc_lighttpd.conf' => '/etc/lighttpd/lighttpd.conf'
						),
						'commands' => Array(
							$configcommand['vhost'],
							$configcommand['diroptions'],
							$configcommand['v_inclighty'],
							$configcommand['d_inclighty'],
							'mkdir -p ' . $settings['system']['documentroot_prefix'],
							'mkdir -p ' . $settings['system']['logfiles_directory'],
							($settings['system']['deactivateddocroot'] != '') ? 'mkdir -p ' . $settings['system']['deactivateddocroot'] : ''
						),
						'restart' => Array(
							'rc-update add lighttpd default',
							'/etc/init.d/lighttpd restart'
						)
					)
				)
			),
			'dns' => Array(
				'label' => $lng['admin']['configfiles']['dns'],
				'daemons' => Array(
					'bind' => Array(
						'label' => 'Bind9 Nameserver',
						'files' => Array(
							'etc_bind_default.zone' => '/etc/bind/default.zone'
						),
						'commands' => Array(
							'echo "include \"' . $settings['system']['bindconf_directory'] . 'froxlor_bind.conf\";" >> /etc/bind/named.conf',
							'touch ' . $settings['system']['bindconf_directory'] . 'froxlor_bind.conf',
							'chown root:0 ' . $settings['system']['bindconf_directory'] . 'froxlor_bind.conf',
							'chmod 0600 ' . $settings['system']['bindconf_directory'] . 'froxlor_bind.conf'
						),
						'restart' => Array(
							'rc-update add named default',
							'/etc/init.d/named restart'
						)
					),
				)
			),
			'smtp' => Array(
				'label' => $lng['admin']['configfiles']['smtp'],
				'daemons' => Array(
					'postfix' => Array(
						'label' => 'Postfix',
						'commands_1' => Array(
							'mkdir -p ' . $settings['system']['vmail_homedir'],
							'chown -R vmail:vmail ' . $settings['system']['vmail_homedir'],
							'chmod 0750 ' . $settings['system']['vmail_homedir'],
							'mv /etc/postfix/main.cf /etc/postfix/main.cf.gentoo',
							'touch /etc/postfix/main.cf',
							'touch /etc/postfix/master.cf',
							'touch /etc/postfix/mysql-virtual_alias_maps.cf',
							'touch /etc/postfix/mysql-virtual_mailbox_domains.cf',
							'touch /etc/postfix/mysql-virtual_mailbox_maps.cf',
							'touch /etc/sasl2/smtpd.conf',
							'chown root:root /etc/postfix/main.cf',
							'chown root:root /etc/postfix/master.cf',
							'chown root:postfix /etc/postfix/mysql-virtual_alias_maps.cf',
							'chown root:postfix /etc/postfix/mysql-virtual_mailbox_domains.cf',
							'chown root:postfix /etc/postfix/mysql-virtual_mailbox_maps.cf',
							'chown root:root /etc/sasl2/smtpd.conf',
							'chmod 0644 /etc/postfix/main.cf',
							'chmod 0644 /etc/postfix/master.cf',
							'chmod 0640 /etc/postfix/mysql-virtual_alias_maps.cf',
							'chmod 0640 /etc/postfix/mysql-virtual_mailbox_domains.cf',
							'chmod 0640 /etc/postfix/mysql-virtual_mailbox_maps.cf',
							'chmod 0600 /etc/sasl2/smtpd.conf',
						),
						'files' => Array(
							'etc_postfix_main.cf' => '/etc/postfix/main.cf',
							'etc_postfix_master.cf' => '/etc/postfix/master.cf',
							'etc_postfix_mysql-virtual_alias_maps.cf' => '/etc/postfix/mysql-virtual_alias_maps.cf',
							'etc_postfix_mysql-virtual_mailbox_domains.cf' => '/etc/postfix/mysql-virtual_mailbox_domains.cf',
							'etc_postfix_mysql-virtual_mailbox_maps.cf' => '/etc/postfix/mysql-virtual_mailbox_maps.cf',
							'etc_sasl2_smtpd.conf' => '/etc/sasl2/smtpd.conf'
						),
						'restart' => Array(
							'rc-update add postfix default',
							'/etc/init.d/postfix restart'
						)
					),
					'dkim' => Array(
						'label' => 'DomainKey filter',
						'commands_1' => Array(
							'mkdir -p /etc/postfix/dkim'
						),
						'files' => Array(
							'dkim-filter.conf' => '/etc/postfix/dkim/dkim-filter.conf'
						),
						'commands_2' => Array(
							'chgrp postfix /etc/postfix/dkim/dkim-filter.conf',
							'echo "smtpd_milters = inet:localhost:8891\n
milter_macro_daemon_name = SIGNING\n
milter_default_action = accept\n" >> /etc/postfix/main.cf'
						),
						'restart' => Array(
							'/etc/init.d/dkim-filter restart'
						)
					)
				)
			),
			'mail' => Array(
				'label' => $lng['admin']['configfiles']['mail'],
				'daemons' => Array(
					'courier' => Array(
						'label' => 'Courier-IMAP (POP3/IMAP)',
						'commands_1' => Array(
							'rm /etc/courier/authlib/authdaemonrc',
							'rm /etc/courier/authlib/authmysqlrc',
							'rm /etc/courier-imap/pop3d',
							'rm /etc/courier-imap/imapd',
							'rm /etc/courier-imap/pop3d-ssl',
							'rm /etc/courier-imap/imapd-ssl',
							'touch /etc/courier/authlib/authdaemonrc',
							'touch /etc/courier/authlib/authmysqlrc',
							'touch /etc/courier-imap/pop3d',
							'touch /etc/courier-imap/imapd',
							'touch /etc/courier-imap/pop3d-ssl',
							'touch /etc/courier-imap/imapd-ssl'
						),
						'files' => Array(
							'etc_courier_authlib_authdaemonrc' => '/etc/courier/authlib/authdaemonrc',
							'etc_courier_authlib_authmysqlrc' => '/etc/courier/authlib/authmysqlrc',
							'etc_courier-imap_pop3d' => '/etc/courier-imap/pop3d',
							'etc_courier-imap_imapd' => '/etc/courier-imap/imapd',
							'etc_courier-imap_pop3d-ssl' => '/etc/courier-imap/pop3d-ssl',
							'etc_courier-imap_imapd-ssl' => '/etc/courier-imap/imapd-ssl'
						),
						'commands_2' => Array(
							'chown root:0 /etc/courier/authlib/authdaemonrc',
							'chown root:0 /etc/courier/authlib/authmysqlrc',
							'chown root:0 /etc/courier-imap/pop3d',
							'chown root:0 /etc/courier-imap/imapd',
							'chown root:0 /etc/courier-imap/pop3d-ssl',
							'chown root:0 /etc/courier-imap/imapd-ssl',
							'chmod 0600 /etc/courier/authlib/authdaemonrc',
							'chmod 0600 /etc/courier/authlib/authmysqlrc',
							'chmod 0600 /etc/courier-imap/pop3d',
							'chmod 0600 /etc/courier-imap/imapd',
							'chmod 0600 /etc/courier-imap/pop3d-ssl',
							'chmod 0600 /etc/courier-imap/imapd-ssl'
						),
						'restart' => Array(
							'rc-update add courier-authlib default',
							'rc-update add courier-pop3d default',
							'rc-update add courier-imapd default',
							'/etc/init.d/courier-authlib restart',
							'/etc/init.d/courier-pop3d restart',
							'/etc/init.d/courier-imapd restart'
						)
					),
					'dovecot' => Array(
						'label' => 'Dovecot',
						'commands_1' => Array(
							'mv dovecot.conf dovecot.conf.gentoo',
							'mv dovecot-sql.conf dovecot-sql.conf.gentoo',
							'touch dovecot.conf',
							'touch dovecot-sql.conf',
						),
						'files' => Array(
							'etc_dovecot_dovecot.conf' => '/etc/dovecot/dovecot.conf',
							'etc_dovecot_dovecot-sql.conf' => '/etc/dovecot/dovecot-sql.conf'
						),
						'restart' => Array(
							'/etc/init.d/dovecot restart'
						)
					)
				)
			),
			'ftp' => Array(
				'label' => $lng['admin']['configfiles']['ftp'],
				'daemons' => Array(
					'proftpd' => Array(
						'label' => 'ProFTPd',
						'files' => Array(
							'etc_proftpd_proftpd.conf' => '/etc/proftpd/proftpd.conf'
						),
						'commands' => Array(
							'touch /etc/proftpd/proftpd.conf',
							'chown root:0 /etc/proftpd/proftpd.conf',
							'chmod 0600 /etc/proftpd/proftpd.conf'
						),
						'restart' => Array(
							'rc-update add proftpd default',
							'/etc/init.d/proftpd restart'
						)
					),
				)
			),
			'etc' => Array(
				'label' => $lng['admin']['configfiles']['etc'],
				'daemons' => Array(
					'cron' => Array(
						'label' => 'Crond (cronscript)',
						'files' => Array(
							'etc_cron.d_froxlor' => '/etc/cron.d/froxlor'
						),
						'commands' => Array(
							'touch /etc/cron.d/froxlor',
							'chown root:0 /etc/cron.d/froxlor',
							'chmod 0640 /etc/cron.d/froxlor',
						),
						'restart' => Array(
							'rc-update add vixie-cron default',
							'/etc/init.d/vixie-cron restart'
						)
					),
					'xinetd' => Array(
						'label' => 'xinet.d (froxlor updates in realtime)',
						'commands' => Array(
							'emerge -av xinetd'
						),
						'files' => Array(
							'etc_xinet.d_froxlor' => '/etc/xinetd.d/froxlor'
						),
						'restart' => Array(
							'/etc/init.d/xinetd restart'
						)
					),
					'awstats' => Array(
						'label' => 'Awstats',
						'commands' => Array(
							'emerge awstats',
							'awstats_configure.pl'
						),
						'files' => Array(
							'etc_awstats.model.conf' => '/etc/awstats/awstats.model.conf'
						),
					),
					'libnss' => Array(
						'label' => 'libnss (system login with mysql)',
						'files' => Array(
							'etc_libnss-mysql.cfg' => '/etc/libnss-mysql.cfg',
							'etc_libnss-mysql-root.cfg' => '/etc/libnss-mysql-root.cfg',
							'etc_nsswitch.conf' => '/etc/nsswitch.conf',
						),
						'commands' => Array(
							'emerge -av libnss-mysql',
							'chmod 600 /etc/libnss-mysql.cfg /etc/libnss-mysql-root.cfg'
						),
						'restart' => Array(
							'rc-update add nscd default',
							'/etc/init.d/nscd restart'
						)
					)
				)
			)
		)
	)
);

?>
