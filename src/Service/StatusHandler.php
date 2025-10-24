<?php

namespace Websailing\MemberStatusMailerBundle\Service;

use Contao\Backend;
use Contao\Controller;
use Contao\Database;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\MemberModel;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use Contao\Email;

class StatusHandler extends Backend
{
    public function button(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        $isDisabled = (int)($row['disable'] ?? 0) === 1;
        $label = $isDisabled ? ($GLOBALS['TL_LANG']['tl_member']['msm_inactive'][0] ?? 'Aktivieren') : ($GLOBALS['TL_LANG']['tl_member']['msm_active'][0] ?? 'Deaktivieren');
        $icon = $isDisabled ? 'invisible.svg' : 'visible.svg';
        $url = $this->addToUrl($href.'&id='.$row['id'].'&disable='.$row['disable']);
        return '<a href="'.$url.'" title="'.StringUtil::specialchars($label).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a>';
    }

    public function handle(): void
    {
        $id = (int) Input::get('id');
        // Current value passed in URL as 0/1; in DB the field is typically char(1) NOT NULL default ''
        $cur = (string) Input::get('disable');
        $isCurrentlyDisabled = ($cur === '1');
        // Toggle: if currently disabled (1) -> set 0 (enable), else set 1 (disable)
        $newVal = $isCurrentlyDisabled ? 0 : 1;
        Database::getInstance()->prepare('UPDATE tl_member %s WHERE id=?')->set(['disable' => $newVal])->execute($id);
        // no debug
        $this->sendNotification($id, !$isCurrentlyDisabled); // pass new enabled state
        Controller::redirect('contao?do=member');
    }

    private function sendNotification(int $id, bool $enabled): void
    {
        $member = MemberModel::findByPk($id);
        if (!$member) { return; }
        // Read notification config from settings
        $enabledId = (int) (\Contao\Config::get('msm_nc_enabled_notification') ?: 0);
        $disabledId = (int) (\Contao\Config::get('msm_nc_disabled_notification') ?: 0);
        $notificationId = $enabled ? $enabledId : $disabledId;
        if ($notificationId <= 0) { return; }

        try {
            /** @var \Terminal42\NotificationCenterBundle\NotificationCenter $nc */
            $nc = \Contao\System::getContainer()->get(\Terminal42\NotificationCenterBundle\NotificationCenter::class);
            $tokens = [
                'domain' => Environment::get('host'),
                'status' => $enabled ? 'enabled' : 'disabled',
                'member' => $member->row(),
                'admin_email' => ($GLOBALS['TL_ADMIN_EMAIL'] ?? ('noreply@'.Environment::get('host'))),
            ];
            // Debug before sending
            try {
                $container = System::getContainer();
                $logsDir = (string) ($container->hasParameter('kernel.logs_dir') ? $container->getParameter('kernel.logs_dir') : ((string) $container->getParameter('kernel.project_dir')).'/var/logs');
                @file_put_contents($logsDir.'/member-status-mailer.log', date('c')." SEND NC id=".$notificationId." member=".$id." status=".($enabled?'enabled':'disabled')."\n", FILE_APPEND);
            } catch (\Throwable $e) {}
            $nc->sendNotification($notificationId, $tokens);
        } catch (\Throwable $e) {
            Message::addError($e->getMessage());
            try {
                $container = System::getContainer();
                $logsDir = (string) ($container->hasParameter('kernel.logs_dir') ? $container->getParameter('kernel.logs_dir') : ((string) $container->getParameter('kernel.project_dir')).'/var/logs');
                @file_put_contents($logsDir.'/member-status-mailer.log', date('c')." ERROR ".$e->getMessage()."\n", FILE_APPEND);
            } catch (\Throwable $e2) {}
        }
    }
}
