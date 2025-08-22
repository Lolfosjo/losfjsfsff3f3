<?php

namespace foxymc\ffa\manager;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\block\Air;
use foxymc\ffa\Main;

class GameManager {
    
    private Main $plugin;
    private string $currentMap = "map1";
    private bool $gameRunning = false;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function setCurrentMap(string $mapId): void {
        $maps = $this->plugin->getConfig()->get("maps", []);
        if (isset($maps[$mapId])) {
            $this->currentMap = $mapId;
        }
    }

    public function getCurrentMap(): string {
        return $this->currentMap;
    }

    public function getCurrentMapName(): string {
        $maps = $this->plugin->getConfig()->get("maps", []);
        return $maps[$this->currentMap]["name"] ?? "Unknown Map";
    }

    public function getAvailableMaps(): array {
        return $this->plugin->getConfig()->get("maps", []);
    }

    public function isGameRunning(): bool {
        return $this->gameRunning;
    }

    public function startGame(): void {
        $this->gameRunning = true;
        $players = Server::getInstance()->getOnlinePlayers();
        
        foreach ($players as $player) {
            $this->teleportPlayerToFFA($player);
        }
        
        Server::getInstance()->broadcastMessage(TextFormat::GREEN . "FFA Game gestartet auf " . $this->getCurrentMapName() . "!");
    }

    public function stopGame(): void {
        $this->gameRunning = false;
        $players = Server::getInstance()->getOnlinePlayers();
        
        foreach ($players as $player) {
            $this->teleportPlayerToLobby($player);
        }
        
        Server::getInstance()->broadcastMessage(TextFormat::RED . "FFA Game beendet! Alle Spieler wurden zur Lobby teleportiert.");
    }

    public function teleportPlayerToLobby(Player $player): void {
        $lobbyConfig = $this->plugin->getConfig()->get("lobby-spawn");
        $world = Server::getInstance()->getWorldManager()->getWorldByName($lobbyConfig["world"]);
        
        if ($world !== null) {
            $position = new Position(
                $lobbyConfig["x"],
                $lobbyConfig["y"],
                $lobbyConfig["z"],
                $world
            );
            $player->teleport($position);
        }
    }

    public function teleportPlayerToFFA(Player $player): bool {
        $maps = $this->plugin->getConfig()->get("maps", []);
        $currentMapConfig = $maps[$this->currentMap] ?? null;
        
        if ($currentMapConfig === null) {
            $player->sendMessage(TextFormat::RED . "Map-Konfiguration nicht gefunden!");
            return false;
        }

        $spawnArea = $currentMapConfig["spawn-area"];
        $worldName = $currentMapConfig["world"];
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        
        if ($world === null) {
            $player->sendMessage(TextFormat::RED . "Welt '{$worldName}' wurde nicht gefunden!");
            return false;
        }

        $x = mt_rand($spawnArea["x-min"], $spawnArea["x-max"]);
        $z = mt_rand($spawnArea["z-min"], $spawnArea["z-max"]);

        $groundY = $world->getHighestBlockAt($x, $z);
        if ($groundY === -1) {
            $player->sendMessage(TextFormat::RED . "Es konnte keine sichere Position gefunden werden");
            return false;
        }

        $y = $groundY + 1;

        $feetBlock = $world->getBlockAt($x, $y, $z);
        $headBlock = $world->getBlockAt($x, $y + 1, $z);

        if (!($feetBlock instanceof Air) || !($headBlock instanceof Air)) {
            $safeY = null;
            for ($currentY = $y; $currentY <= $y + 20; $currentY++) {
                $currentFeet = $world->getBlockAt($x, $currentY, $z);
                $currentHead = $world->getBlockAt($x, $currentY + 1, $z);
                if (($currentFeet instanceof Air) && ($currentHead instanceof Air)) {
                    $safeY = $currentY;
                    break;
                }
            }
            if ($safeY === null) {
                $player->sendMessage(TextFormat::RED . "Es konnte keine sichere Position gefunden werden");
                return false;
            }
            $y = $safeY;
        }

        $position = new Position($x, $y, $z, $world);
        $player->teleport($position);

        return true;
    }
}