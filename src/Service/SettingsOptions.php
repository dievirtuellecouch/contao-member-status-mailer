<?php

namespace Websailing\MemberStatusMailerBundle\Service;

use Contao\System;

class SettingsOptions
{
    public static function onLoad(): void
    {
        try {
            $container = System::getContainer();
            $db = $container->get('database_connection');
            $rows = $db->createQueryBuilder()->select('id','title','type')->from('tl_nc_notification')->orderBy('title','ASC')->executeQuery()->fetchAllAssociative();
            $map = [];
            foreach ($rows as $r) {
                $label = (string) ($r['title'] ?? '') . ' [' . (string) ($r['type'] ?? '') . ']';
                $map[(int) $r['id']] = $label;
            }
            $GLOBALS['TL_DCA']['tl_settings']['fields']['msm_nc_enabled_notification']['options'] = $map ?: [];
            $GLOBALS['TL_DCA']['tl_settings']['fields']['msm_nc_disabled_notification']['options'] = $map ?: [];
            unset($GLOBALS['TL_DCA']['tl_settings']['fields']['msm_nc_enabled_notification']['options_callback']);
            unset($GLOBALS['TL_DCA']['tl_settings']['fields']['msm_nc_disabled_notification']['options_callback']);
            // no debug
        } catch (\Throwable $e) {}
    }
    public static function enabled(): array
    {
        return self::allWithLog('enabled');
    }

    public static function disabled(): array
    {
        return self::allWithLog('disabled');
    }

    private static function allWithLog(string $context): array
    {
        try {
            $container = System::getContainer();
            $db = $container->get('database_connection');
            $list = $db->createQueryBuilder()
                ->select('id','title')
                ->from('tl_nc_notification')
                ->orderBy('title','ASC')
                ->executeQuery()
                ->fetchAllKeyValue();

            // no debug

            return $list ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
