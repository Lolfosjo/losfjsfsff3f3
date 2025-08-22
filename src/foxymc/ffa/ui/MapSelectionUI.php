<?php

namespace foxymc\ffa\ui;

use pocketmine\player\Player;
use pocketmine\form\Form;
use pocketmine\utils\TextFormat;
use foxymc\ffa\Main;

class MapSelectionUI implements Form {
    
    private Main $plugin;
    
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }
    
    public function sendMapSelectionForm(Player $player): void {
        $player->sendForm($this);
    }
    
    public function jsonSerialize(): array {
        $maps = $this->plugin->getGameManager()->getAvailableMaps();
        $buttons = [];
        
        foreach ($maps as $mapId => $mapData) {
            $buttons[] = [
                "text" => $mapData["name"],
                "image" => [
                    "type" => "path",
                    "data" => "textures/blocks/grass_side_carried"
                ]
            ];
        }
        
        return [
            "type" => "form",
            "title" => "§bMap Auswahl",
            "content" => "§7Wähle eine Map für das nächste FFA Game:\n\n§eCurrent: §a" . $this->plugin->getGameManager()->getCurrentMapName(),
            "buttons" => $buttons
        ];
    }
    
    public function handleResponse(Player $player, $data): void {
        if ($data === null) {
            return;
        }
        
        $maps = array_keys($this->plugin->getGameManager()->getAvailableMaps());
        
        if (isset($maps[$data])) {
            $selectedMapId = $maps[$data];
            $mapData = $this->plugin->getGameManager()->getAvailableMaps()[$selectedMapId];
            
            $this->plugin->getGameManager()->setCurrentMap($selectedMapId);
            $player->sendMessage(TextFormat::GREEN . "Map wurde auf '" . $mapData["name"] . "' gesetzt!");
        }
    }
}