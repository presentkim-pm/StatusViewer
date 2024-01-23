<?php

/**
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace kim\present\statusviewer;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\ObjectSet;
use pocketmine\utils\Process;
use pocketmine\utils\TextFormat;

final class Loader extends PluginBase{
    /** @phpstan-var ObjectSet<Player> */
    private ObjectSet $viewers;

    protected function onEnable() : void{
        $this->viewers = new ObjectSet();
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
            $server = $this->getServer();

            $recipients = array_filter($server->getOnlinePlayers(), [$this, "isViewer"]);
            if(!empty($recipients)){
                $threadCount = Process::getThreadCount();
                $totalMemory = number_format(round((Process::getAdvancedMemoryUsage()[1] / 1024) / 1024, 2), 2);

                $worldCount = 0;
                $chunkCount = 0;
                $entityCount = 0;
                $tileCount = 0;
                foreach($server->getWorldManager()->getWorlds() as $world){
                    ++$worldCount;
                    $entityCount += count($world->getEntities());
                    foreach($world->getLoadedChunks() as $chunk){
                        ++$chunkCount;
                        $tileCount += count($chunk->getTiles());
                    }
                }
                $statusMessage =
                    "Server: {$server->getName()}_v{$server->getApiVersion()} (PHP " . phpversion() . ")\n" .
                    "TPS: {$server->getTicksPerSecond()} ({$server->getTickUsage()}%)\n" .
                    "Threads: $threadCount, Memory: $totalMemory MB\n" .
                    "World($worldCount) Chunk: $chunkCount, Entity: $entityCount, Tile: $tileCount";

                $server->broadcastTip($statusMessage, $recipients);
            }
        }), 2);
    }

    /** @param string[] $args */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "It can only be used in-game");
            return true;
        }

        if($this->isViewer($sender)){
            $this->removeViewer($sender);
            $sender->sendMessage(TextFormat::AQUA . "[StatusViewer] Disable status viewer");
        }else{
            $this->addViewer($sender);
            $sender->sendMessage(TextFormat::AQUA . "[StatusViewer] Enable status viewer");
        }
        return true;
    }

    public function isViewer(Player $player) : bool{
        return $this->viewers->contains($player);
    }

    public function addViewer(Player $player) : void{
        $this->viewers->add($player);
    }

    public function removeViewer(Player $player) : void{
        $this->viewers->remove($player);
    }
}