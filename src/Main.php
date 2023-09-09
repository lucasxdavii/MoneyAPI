<?php
/*
 * MoneyAPI - A plugin for managing player money
 * Copyright (c) 2023 LucaSxDavii
 * This plugin is licensed under the MIT License.
 * Visit https://github.com/lucasxdavii/MoneyAPI for more information.
 * 
 *   __  __                                 _____ _____ 
 *  |  \/  |                          /\   |  __ \_   _|
 *  | \  / | ___  _ __   ___ _   _   /  \  | |__) || |  
 *  | |\/| |/ _ \| '_ \ / _ \ | | | / /\ \ |  ___/ | |  
 *  | |  | | (_) | | | |  __/ |_| |/ ____ \| |    _| |_ 
 *  |_|  |_|\___/|_| |_|\___|\__, /_/    \_\_|   |_____|
 *                           __/ |                     
 *                          |___/                     
 */
namespace lucasxdavii\MoneyAPI;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use Joshet18\CustomScoreboard\PlayerScoreTagEvent;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\Server;

class Main extends PluginBase implements Listener {

    private $playerMoney = [];

    public function onEnable(): void {
        $this->getLogger()->info("MoneyAPI has been enabled.");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);  
        $this->loadPlayerMoneyData();
    }

    public function onDisable(): void {
        $this->getLogger()->info("MoneyAPI has been disabled.");

        $this->savePlayerMoneyData();
    }

    public function onPlayerJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $playerName = $player->getName();
    
        $config = new Config($this->getDataFolder() . "player_money.yml", Config::YAML);
    
        if (!$config->exists($playerName)) {
            $config->set($playerName, 0);
            $config->save();
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "money") {
            if (!$sender instanceof Player) {
                $sender->sendMessage(TextFormat::RED . "Este comando só pode ser usado por jogadores.");
                return true;
            }
    
            if (isset($args[0])) {
                if ($args[0] === "givemoney" && isset($args[1]) && isset($args[2])) {
                    if ($sender->hasPermission("moneyapi.admin")) {
                        $targetPlayerName = $args[1];
                        $targetPlayer = $this->getServer()->getPlayerExact($targetPlayerName);
    
                        if ($targetPlayer instanceof Player) {
                            if (!is_numeric($args[2])) {
                                $sender->sendMessage(TextFormat::RED . "Por favor, insira um valor numérico válido.");
                                return true;
                            }
    
                            $amount = (int)$args[2];
                            $this->setPlayerMoney($targetPlayer, $this->getPlayerMoney($targetPlayer) + $amount);
                            $this->savePlayerMoneyData();
    
                            $sender->sendMessage("Você deu " . $amount . " de dinheiro para " . $targetPlayerName);
                            $targetPlayer->sendMessage("Você recebeu " . $amount . " de dinheiro de " . $sender->getName());
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Jogador não encontrado ou offline.");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Você não tem permissão para dar dinheiro a outros jogadores.");
                    }
                    
                    return true;
                }
            }
    
            $currentMoney = $this->getPlayerMoney($sender);
            $sender->sendMessage("Você tem " . $currentMoney . " de dinheiro.");
            return true;
        }
        return false;
    }

    public function getPlayerMoney(Player $player): int {
        $playerName = $player->getName();
        $config = new Config($this->getDataFolder() . "player_money.yml", Config::YAML);
        return $config->get($playerName, 0);
    }

    public function setPlayerMoney(Player $player, int $amount): void {
        $playerName = $player->getName();
        $config = new Config($this->getDataFolder() . "player_money.yml", Config::YAML);
        $config->set($playerName, $amount);
        $config->save();
        $this->playerMoney[$playerName] = $amount;
    }

    private function loadPlayerMoneyData(): void {
        $config = new Config($this->getDataFolder() . "player_money.yml", Config::YAML);
/*
        foreach ($config->getAll() as $playerName => $amount) {
            $this->setPlayerMoney($this->getServer()->getPlayerExact($playerName), $amount);
        }*/
        foreach ($config->getAll() as $playerName => $amount) {
            $player = $this->getServer()->getPlayerExact($playerName);
            if ($player !== null) {
            $this->setPlayerMoney($player, $amount);
            }
        }

    }

    private function savePlayerMoneyData(): void {
        $config = new Config($this->getDataFolder() . "player_money.yml", Config::YAML);

        foreach ($this->getPlayerMoneyData() as $playerName => $amount) {
            $config->set($playerName, $amount);
        }

        $config->save();
    }

    public function getPlayerMoneyData(): array {
        return $this->playerMoney;
    }

    public function onPlayerTags(PlayerScoreTagEvent $ev): void {
        $player = $ev->getPlayer();
        $tags = $ev->getTags();

        if ($player instanceof Player) {
            $tags = $this->processTags($player, $tags);
            $ev->setTags($tags);
        }
    }

    private function processTags(Player $player, array $tags): array {
        $result = [];
        foreach ($tags as $tag) {
            $moneyTag = str_replace("{money}", "$" . $this->getPlayerMoneyFromArray($player), $tag);
            $result[] = $moneyTag;
        }
        return $result;
    }

    private function getPlayerMoneyFromArray(Player $player): int {
        $playerName = $player->getName();
        if (isset($this->playerMoney[$playerName])) {
            return $this->playerMoney[$playerName];
        }
        return 0;
    }
}