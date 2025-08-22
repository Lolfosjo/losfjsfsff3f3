<?php

namespace foxymc\ffa\scoreboard;

use DeathSpectator\Main as StatsSystemPlugin;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Server;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use foxymc\ffa\Main;
use foxymc\ffa\scoreboard\ScoreboardUpdateTask;
use pocketmine\console\ConsoleCommandSender;

class Scoreboard implements Listener
{
    private Main $plugin;
    private array $tasks = [];
    private array $playerScoreboards = [];


    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $this->createScoreboard($player);
        $this->startScoreboardUpdates($player);
    }

    public function onRespawn(PlayerRespawnEvent $event): void {
        $player = $event->getPlayer();
        
        $this->plugin->getScheduler()->scheduleDelayedTask(
            new class($player, $this->plugin) extends Task {
                private Player $player;
                private Main $plugin;
                
                public function __construct(Player $player, Main $plugin) {
                    $this->player = $player;
                    $this->plugin = $plugin;
                }
                
                public function onRun(): void {
                    if ($this->player->isOnline()) {
                        $server = Server::getInstance();
                        $consoleSender = new ConsoleCommandSender($server, $server->getLanguage());
                        $server->dispatchCommand($consoleSender, "start " . $this->player->getName());
                    }
                }
            },
            109
        );
        
        $this->plugin->getScheduler()->scheduleDelayedTask(
            new class($player, $this) extends Task {
                private Player $player;
                private Scoreboard $scoreboard;
                
                public function __construct(Player $player, Scoreboard $scoreboard) {
                    $this->player = $player;
                    $this->scoreboard = $scoreboard;
                }
                
                public function onRun(): void {
                    if ($this->player->isOnline()) {
                        $this->scoreboard->recreateScoreboard($this->player);
                    }
                }
            },
            100
        );
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $this->stopScoreboardUpdates($player);
        $this->removePlayerFromTracking($player);
    }

    private function startScoreboardUpdates(Player $player): void {
        $playerName = $player->getName();
        
        $this->stopScoreboardUpdates($player);
        
        $task = $this->plugin->getScheduler()->scheduleRepeatingTask(
            new ScoreboardUpdateTask($player, $this),
            100
        );
        
        $this->tasks[$playerName] = $task;
    }

    public function stopScoreboardUpdates(Player $player): void {
        $playerName = $player->getName();
        
        if (isset($this->tasks[$playerName])) {
            $this->tasks[$playerName]->cancel();
            unset($this->tasks[$playerName]);
        }
    }

    private function removePlayerFromTracking(Player $player): void {
        $playerName = $player->getName();
        unset($this->playerScoreboards[$playerName]);
    }

    public function createScoreboard(Player $player): void {
        $playerName = $player->getName();
        
        $this->removeScoreboard($player);
        
        $currentData = $this->getScoreboardData($playerName);
        
        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = "sidebar";
        $pk->objectiveName = "Mein Servername";
        $pk->displayName = "acm.scoreboard.logo";
        $pk->criteriaName = "dummy";
        $pk->sortOrder = 0;
        $player->getNetworkSession()->sendDataPacket($pk);

        $this->setScore($player, "§bSpieler Online§7:", 0);
        $this->setScore($player, "§e" . $currentData['onlinePlayers'], 1);
        $this->setScore($player, "§bKills§7:", 2);
        $this->setScore($player, "§e" . $currentData['kills'], 3);
        $this->setScore($player, "§bTode§7:", 4);
        $this->setScore($player, "§e" . $currentData['deaths'], 5);
        $this->setScore($player, "§bK/D§7:", 6);
        $this->setScore($player, "§e" . $currentData['kd'], 7);

        $this->playerScoreboards[$playerName] = true;
    }

    private function getScoreboardData(string $playerName): array {
        $onlinePlayers = count(Server::getInstance()->getOnlinePlayers());
        $statsPlugin = Server::getInstance()->getPluginManager()->getPlugin("StatsSystem");
        
        $kills = 0;
        $deaths = 0;
        $kd = "0.00";
        
        if ($statsPlugin !== null && $statsPlugin instanceof StatsSystemPlugin) {
            $stats = $statsPlugin->getStatsFor($playerName);
            if ($stats !== null) {
                $kills = $stats->getKills();
                $deaths = $stats->getDeaths();
                $kd = number_format($stats->getKillDeathRatio(), 2);
            }
        }
        
        return [
            'onlinePlayers' => $onlinePlayers,
            'kills' => $kills,
            'deaths' => $deaths,
            'kd' => $kd
        ];
    }

    public function recreateScoreboard(Player $player): void {
        $this->removeScoreboard($player);
        $this->createScoreboard($player);
        $this->startScoreboardUpdates($player);
    }

    public function updateScoreboardContent(Player $player): void {
        if (!$player->isOnline()) {
            $this->stopScoreboardUpdates($player);
            return;
        }

        $playerName = $player->getName();
        if (!isset($this->playerScoreboards[$playerName])) {
            return;
        }

        $this->removeScoreboard($player);
        $this->createScoreboard($player);
    }



    public function removeScoreboard(Player $player): void {
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = "Mein Servername";
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function setScore(Player $player, string $message, int $score): void {
        $pk = new SetScorePacket();
        $pk->type = SetScorePacket::TYPE_CHANGE;
        
        $entry = new ScorePacketEntry();
        $entry->objectiveName = "Mein Servername";
        $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
        $entry->customName = $message;
        $entry->score = $score;
        $entry->scoreboardId = $score;
        
        $pk->entries[] = $entry;
        $player->getNetworkSession()->sendDataPacket($pk);
    }
    
    public function updateAllScoreboards(): void {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if (isset($this->playerScoreboards[$player->getName()])) {
                $this->updateScoreboardContent($player);
            }
        }
    }
    
    public function shutdown(): void {
        foreach ($this->tasks as $task) {
            $task->cancel();
        }
        $this->tasks = [];
        $this->playerScoreboards = [];
    }
}