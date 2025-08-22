<?php

namespace foxymc\ffa\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use foxymc\ffa\Main;

class start extends Command {
    
    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("start", "Starte FFA oder teleportiere Spieler", "/start [spieler]", []);
        $this->setPermission("ffa.start");
        $this->plugin = $plugin;
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
                return false;
            }
        } else {
            if ($this->plugin->getGameManager()->isGameRunning()) {
                $sender->sendMessage(TextFormat::RED . "Das FFA Game läuft bereits!");
                return false;
            }
            
            $this->plugin->getGameManager()->startGame();
            $sender->sendMessage(TextFormat::GREEN . "FFA Game wurde gestartet!");
            return true;
        }

        if($target->getGamemode() == GameMode::SPECTATOR()){
            $sender->sendMessage(TextFormat::RED . "Dieser Spieler kann jetzt nicht teleportiert werden!");
            return false;
        }

        $success = $this->plugin->getGameManager()->teleportPlayerToFFA($target);
        
        if ($success) {
            if ($sender !== $target) {
                $sender->sendMessage(TextFormat::GREEN . "Spieler " . $target->getName() . " wurde zur FFA Map teleportiert!");
            }
            
            if ($this->plugin->getGameManager()->isGameRunning()) {
                $eventListener = new \foxymc\ffa\listener\Eventlistener($this->plugin);
                $eventListener->giveKit($target);
            }
        }

        return true;
    }
}