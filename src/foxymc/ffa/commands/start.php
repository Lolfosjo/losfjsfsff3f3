<?php

namespace foxymc\ffa\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\utils\TextFormat;
use pocketmine\block\Air;
use pocketmine\Server;

class start extends Command {

    public function __construct() {
        parent::__construct("start", "Starte", "/start {spieler}", []);
        $this->setPermission("ffa.start");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (isset($args[0])) {
            $target = Server::getInstance()->getPlayerExact($args[0]);
            if (!$target instanceof Player) {
                $sender->sendMessage(TextFormat::RED . "Spieler '{$args[0]}' wurde nicht gefunden.");
                return false;
            }
        } elseif ($sender instanceof Player) {
            $target = $sender;
            if($target->getGamemode() == GameMode::SPECTATOR()){
                $sender->sendMessage(TextFormat::RED . "Du kannst das jetzt nicht nutzen!");
            }
        } else {
            $sender->sendMessage(TextFormat::RED . "Bitte gib einen Spielernamen an.");
            return false;
        }

        $x = mt_rand(245, 281);
        $z = mt_rand(253, 288);
        $world = $target->getWorld();

        $groundY = $world->getHighestBlockAt($x, $z);
        if ($groundY === -1) {
            $sender->sendMessage(TextFormat::RED . "Es konnte leider keine sichere Position gefunden werden");
            return false;
        }
        if($target->getGamemode() == GameMode::SPECTATOR()){
            $sender->sendMessage(TextFormat::RED . "Dieser Spieler kann jetzt nicht Teleportiert werden!");
        }
        $y = $groundY + 1;

        $feetBlock = $world->getBlockAt($x, $y, $z);
        $headBlock = $world->getBlockAt($x, $y + 1, $z);

        if (!($feetBlock instanceof Air) || !($headBlock instanceof Air)) {
            $safeY = null;
            for ($currentY = $y; $currentY <= 69; $currentY++) {
                $currentFeet = $world->getBlockAt($x, $currentY, $z);
                $currentHead = $world->getBlockAt($x, $currentY + 1, $z);
                if (($currentFeet instanceof Air) && ($currentHead instanceof Air)) {
                    $safeY = $currentY;
                    break;
                }
            }
            if ($safeY === null) {
                $sender->sendMessage(TextFormat::RED . "Es konnte leider keine sichere Position gefunden werden");
                return false;
            }
            $y = $safeY;
        }

        $position = new Position($x, $y, $z, $world);
        $target->teleport($position);

        if ($sender !== $target) {

        }

        return true;
    }
}