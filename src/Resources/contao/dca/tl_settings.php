<?php

// Legend
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{msm_legend},msm_nc_enabled_notification,msm_nc_disabled_notification';

// Ensure options exist even if callbacks fail
$GLOBALS['TL_DCA']['tl_settings']['config']['onload_callback'][] = [\Websailing\MemberStatusMailerBundle\Service\SettingsOptions::class, 'onLoad'];

$GLOBALS['TL_DCA']['tl_settings']['fields']['msm_nc_enabled_notification'] = [
    'label' => ['Notification (Mitglied aktiviert)', 'Wird versendet, wenn ein Mitglied aktiviert wird.'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => static function () {
        try {
            $container = \Contao\System::getContainer();
            $nc = $container->get(\Terminal42\NotificationCenterBundle\NotificationCenter::class);
            $list = $nc->getNotificationsForNotificationType(\Terminal42\NotificationCenterBundle\NotificationType\MemberActivationNotificationType::NAME);
            if (!\is_array($list) || !\count($list)) {
                // Fallback: alle Notifications anzeigen
                try {
                    $db = $container->get('database_connection');
                    $list = $db->createQueryBuilder()->select('id','title')->from('tl_nc_notification')->orderBy('title','ASC')->executeQuery()->fetchAllKeyValue();
                } catch (\Throwable $e2) {}
            }
            return $list ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    },
    'eval' => ['includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'],
    'sql'  => "int(10) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['msm_nc_disabled_notification'] = [
    'label' => ['Notification (Mitglied deaktiviert)', 'Wird versendet, wenn ein Mitglied deaktiviert wird.'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => static function () {
        try {
            $container = \Contao\System::getContainer();
            $nc = $container->get(\Terminal42\NotificationCenterBundle\NotificationCenter::class);
            $list = $nc->getNotificationsForNotificationType(\Terminal42\NotificationCenterBundle\NotificationType\MemberActivationNotificationType::NAME);
            if (!\is_array($list) || !\count($list)) {
                try {
                    $db = $container->get('database_connection');
                    $list = $db->createQueryBuilder()->select('id','title')->from('tl_nc_notification')->orderBy('title','ASC')->executeQuery()->fetchAllKeyValue();
                } catch (\Throwable $e2) {}
            }
            return $list ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    },
    'eval' => ['includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'],
    'sql'  => "int(10) unsigned NOT NULL default '0'",
];
