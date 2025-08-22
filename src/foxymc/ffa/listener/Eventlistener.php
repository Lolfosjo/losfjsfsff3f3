<?php

declare(strict_types=1);

namespace foxymc\ffa\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use foxymc\ffa\Main;
use pocketmine\player\GameMode;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\Server;

class Eventlistener implements Listener {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $event->setJoinMessage("§vFoxyMC§r >> " . $player->getName() . " §a ist FFA beigetreten!");
        $event->getPlayer()->setGamemode(GameMode::ADVENTURE());
        $event->getPlayer()->getInventory()->clearAll();
        $this->giveKit($player);
        $server = Server::getInstance();
        $consoleSender = new ConsoleCommandSender($server, $server->getLanguage());
        $success = $server->dispatchCommand($consoleSender, "start " . $player->getName());
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
            $this->giveKit($player);
            $event->getPlayer()->setGamemode(GameMode::ADVENTURE());
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
}