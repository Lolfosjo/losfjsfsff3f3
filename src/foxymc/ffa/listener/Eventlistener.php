<?php

declare(strict_types=1);

namespace foxymc\ffa\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\player\GameMode;
use pocketmine\utils\TextFormat;
use foxymc\ffa\Main;
use foxymc\ffa\ui\MapSelectionUI;

class Eventlistener implements Listener {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $event->setJoinMessage("§vFoxyMC§r >> " . $player->getName() . " §a ist FFA beigetreten!");
        $player->setGamemode(GameMode::ADVENTURE());
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        
        $this->plugin->getGameManager()->teleportPlayerToLobby($player);
        
        if ($player->hasPermission("ffa.admin")) {
            $this->giveAdminItems($player);
        }
    }

    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $event->setQuitMessage("§vFoxyMC§r >> " . $player->getName() . " §a hat FFA verlassen!");
    }

    public function onRespawn(PlayerRespawnEvent $event) {
        $player = $event->getPlayer();
        if ($player->isOnline()) {
            if ($this->plugin->getGameManager()->isGameRunning()) {
                $this->plugin->getScheduler()->scheduleDelayedTask(
                    new class($player, $this->plugin) extends \pocketmine\scheduler\Task {
                        private Player $player;
                        private Main $plugin;
                        
                        public function __construct(Player $player, Main $plugin) {
                            $this->player = $player;
                            $this->plugin = $plugin;
                        }
                        
                        public function onRun(): void {
                            if ($this->player->isOnline()) {
                                $this->plugin->getGameManager()->teleportPlayerToFFA($this->player);
                                $this->giveKit($this->player);
                            }
                        }

                        private function giveKit(Player $player) {
                            $player->getInventory()->setItem(0, VanillaItems::DIAMOND_SWORD()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 500)));
                            $player->getInventory()->setItem(1, VanillaItems::BOW()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 500)));
                            $player->getInventory()->setItem(2, VanillaItems::ARROW()->setCount(16));
                            $this->giveArmor($player);
                        }

                        private function giveArmor(Player $player) {
                            $armor = $player->getArmorInventory();
                            $armor->setHelmet(VanillaItems::IRON_HELMET()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 500)));
                            $armor->setChestplate(VanillaItems::IRON_CHESTPLATE()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 500)));
                            $armor->setLeggings(VanillaItems::IRON_LEGGINGS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 500)));
                            $armor->setBoots(VanillaItems::IRON_BOOTS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 500)));
                        }
                    },
                    20
                );
            } else {
                $this->plugin->getGameManager()->teleportPlayerToLobby($player);
                if ($player->hasPermission("ffa.admin")) {
                    $this->giveAdminItems($player);
                }
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();
        
        if ($item->hasCustomName()) {
            switch ($item->getCustomName()) {
                case "§aSpiel Starten":
                    if ($player->hasPermission("ffa.admin")) {
                        if (!$this->plugin->getGameManager()->isGameRunning()) {
                            $this->plugin->getGameManager()->startGame();
                            $player->getInventory()->clearAll();
                            $this->giveKit(player: $player);
                            $player->sendMessage(TextFormat::GREEN . "FFA Game wurde gestartet!");
                        } else {
                            $player->sendMessage(TextFormat::RED . "Das Spiel läuft bereits!");
                        }
                    }
                    $event->cancel();
                    break;
                    
                case "§bMap Auswahl":
                    if ($player->hasPermission("ffa.admin")) {
                        $ui = new MapSelectionUI($this->plugin);
                        $ui->sendMapSelectionForm($player);
                    }
                    $event->cancel();
                    break;
            }
        }
    }

    public function onBlockBreak(BlockBreakEvent $event) {
        if (!$event->getPlayer()->hasPermission("ffa.block.break")) {
            $event->cancel();
            $event->getPlayer()->sendActionBarMessage("§cDazu hast du keine Rechte!");
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event) {
        if (!$event->getPlayer()->hasPermission("ffa.block.place")) {
            $event->cancel();
            $event->getPlayer()->sendActionBarMessage("§cDazu hast du keine Rechte!");
        }
    }

    public function onDrop(PlayerDropItemEvent $event) {
        $player = $event->getPlayer();
        if (!$player->hasPermission("ffa.item.drop")) {
            $event->cancel();
            $player->sendActionBarMessage("§cDazu hast du keine Rechte!");
        }
    }

    public function onHunger(PlayerExhaustEvent $event) {
        $event->cancel();
    }

    public function onDeath(PlayerDeathEvent $event) {
        $event->setDrops([]);
        $player = $event->getPlayer();
        $player->getArmorInventory()->clearAll();
        $player->getInventory()->clearAll();

        $cause = $player->getLastDamageCause();
        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if ($damager instanceof Player) {
                $damager->setHealth($damager->getMaxHealth());
            }
        }
    }

    private function giveAdminItems(Player $player) {
        $startItem = VanillaItems::EMERALD();
        $startItem->setCustomName("§aSpiel Starten");
        $startItem->setLore(["§7Rechtsklick zum Starten des FFA Games"]);
        $player->getInventory()->setItem(0, $startItem);
        
        $mapItem = VanillaItems::COMPASS();
        $mapItem->setCustomName("§bMap Auswahl");
        $mapItem->setLore(["§7Rechtsklick um die Map zu wählen"]);
        $player->getInventory()->setItem(1, $mapItem);
    }

    public function giveKit(Player $player) {
        $player->getInventory()->setItem(0, VanillaItems::DIAMOND_SWORD()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 500)));
        $player->getInventory()->setItem(1, VanillaItems::BOW()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 500)));
        $player->getInventory()->setItem(2, VanillaItems::ARROW()->setCount(16));
        $this->giveArmor($player);
    }

    private function giveArmor(Player $player) {
        $armor = $player->getArmorInventory();
        $armor->setHelmet(VanillaItems::IRON_HELMET()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 500)));
        $armor->setChestplate(VanillaItems::IRON_CHESTPLATE()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 500)));
        $armor->setLeggings(VanillaItems::IRON_LEGGINGS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 500)));
        $armor->setBoots(VanillaItems::IRON_BOOTS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 500)));
    }
}