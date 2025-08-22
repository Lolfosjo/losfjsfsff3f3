<?php

namespace foxymc\ffa;

use pocketmine\plugin\PluginBase;
use foxymc\ffa\listener\Eventlistener;
use foxymc\ffa\scoreboard\scoreboard;
use foxymc\ffa\commands\start;
use foxymc\ffa\manager\GameManager;

class Main extends PluginBase {

    private $scoreboard;
    private GameManager $gameManager;

    public function onEnable(): void {

        $this->scoreboard = new Scoreboard($this);
        $this->gameManager = new GameManager($this);
        $this->getServer()->getPluginManager()->registerEvents(new Eventlistener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents($this->scoreboard, $this);

        # Commands nur hier registrieren!
        $this->getServer()->getCommandMap()->register("start", new start($this));

        $this->saveDefaultConfig();
        if (!$this->getConfig()->exists("lobby-spawn")) {
            $this->getConfig()->set("lobby-spawn", [
                "world" => "world",
                "x" => 0,
                "y" => 64,
                "z" => 0
            ]);
            $this->getConfig()->set("maps", [
                "map1" => [
                    "name" => "Desert Arena",
                    "world" => "world",
                    "spawn-area" => [
                        "x-min" => 245,
                        "x-max" => 281,
                        "z-min" => 253,
                        "z-max" => 288
                    ]
                ],
                "map2" => [
                    "name" => "Forest Battle",
                    "world" => "world",
                    "spawn-area" => [
                        "x-min" => 100,
                        "x-max" => 150,
                        "z-min" => 100,
                        "z-max" => 150
                    ]
                ]
            ]);
            $this->getConfig()->save();
        }
    }

    public function onDisable(): void {
        if (isset($this->scoreboard)) {
            $this->scoreboard->shutdown();
        }
    }

    public function getScoreboardManager(): Scoreboard {
        return $this->scoreboard;
    }

    public function getGameManager(): GameManager {
        return $this->gameManager;
    }
}