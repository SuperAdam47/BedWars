<?php
 namespace BedWars;
 use pocketmine\math\Vector3;
 use pocketmine\block\Block; use pocketmine\command\Command;
 use pocketmine\command\CommandSender;
 use pocketmine\command\ConsoleCommandSender;
 use pocketmine\IPlayer;
 use pocketmine\utils\Config;
 use pocketmine\permission\PermissionAttachment;
 use pocketmine\permission\Permission;
 use pocketmine\Player;
 use pocketmine\Server;
 use pocketmine\plugin\Plugin;
 use pocketmine\plugin\PluginBase;
 use pocketmine\utils\TextFormat;
 use pocketmine\level\Level;
 use pocketmine\level\Position;
 use pocketmine\level\particle\FloatingTextParticle;
 use pocketmine\item\Item;
 use pocketmine\tile\Tile;
 use pocketmine\tile\Sign;
 use pocketmine\tile\Chest;
 use pocketmine\nbt\tag\Byte;
 use pocketmine\nbt\tag\Compound;
 use pocketmine\nbt\tag\Double;
 use pocketmine\nbt\tag\Enum;
 use pocketmine\nbt\tag\Float;
 use pocketmine\nbt\tag\Int;
 use pocketmine\nbt\tag\Short;
 use pocketmine\nbt\tag\String;
 use pocketmine\event\player\PlayerRespawnEvent;
 use BedWars\EventListener;
 use BedWars\PopupInfo;
 use BedWars\TPTask;
 use BedWars\ExecuteTask;
 use pocketmine\inventory\PlayerInventory;
 use pocketmine\inventory\ChestInventory;
 use pocketmine\inventory\CustomInventory;
 use pocketmine\inventory\InventoryType;
 use pocketmine\entity\Entity;
 use pocketmine\entity\Villager;
 use pocketmine\entity\Item as EntityItem; use pocketmine\entity\Effect;
 
 class BuyingInventory extends CustomInventory
 {
	 protected $client; public function __construct($holder,$client) {
		 $this->client = $client;
		 parent::__construct($holder,InventoryType::get(InventoryType::CHEST),[],null,"");
		 }
		 public function getClient() {
			 return $this->client; }
			 }
			 class BedWarsGameTeam 
			 {
				 public $name = 0;
				 public $Bed = 0;
				 public $Spawn = 0;
				 public $Players = 0;
				 public $BedStatus = 1;
				 function __construct($name = "",$Bed = null,$Spawn = null) {
					 $this->name = $name;
					 $this->Bed = is_null($Bed) ? Array(new Vector3(0,0,0),new Vector3(0,0,0),0) :
					 $Bed;
					 $this->Spawn = is_null($Spawn) ? new Vector3(0,0,0) : $Spawn;
					 $this->Players = Array();
					 $this->BedStatus = 1; }
					 };
					 class BedWarsGame { public $BlocksPlaced = Array();
					 public $Level = 0;
					 public $Lobby = 0;
					 public $plugin = 0;
					 public $LevelData = 0;
					 public $Teams = Array();
					 public $Status = 0;
					 public $SpawnTasks = Array();
					 public $PopupInfo = 0,$PopupInfo2 = 0;
					 function __construct(Level $Level,BedWars $plugin) { $this->Level = $Level; $this->plugin = $plugin;
					 $this->LevelData = (new Config($plugin->getDataFolder()."levels/".$Level->getFolderName().".yml"))->getAll();
					 $this->level_name = $Level->getFolderName();
					 $this->PopupInfo = new PopupInfo($this->plugin,$Level,1);
					 $this->PopupInfo->Rows = Array();
					 foreach ($this->LevelData["teams"] as $name => $team) { /*$this->PopupInfo->Rows[$name] = "[".$this->plugin->teamColorName($name)."] = 0";*/ };
					 $this->PopupInfo2 = new PopupInfo($this->plugin,$Level,0);
					 $this->PopupInfo2->PlayersData = Array();
					 $Level->setAutoSave(false);
					 $Level->setTime(6000);
					 $Level->stopTime();
					 $this->initBlocks();
					 }
					 public function initBlocks() {
						 foreach ($this->LevelData["teams"] as $name => $Team) {
							 $Bed = explode(" ",$Team["bed"]); $Spawn = explode(" ",$Team["spawn"]);
							 $Team = $this->Teams[$name] = new BedWarsGameTeam($name,Array(new Vector3($Bed[0],$Bed[1],$Bed[2]),new Vector3($Bed[3],$Bed[4],$Bed[5]),$Bed[6]),new Vector3($Spawn[0] - 0.5,$Spawn[1],$Spawn[2] + 0.5));
							 $this->Level->setBlock(new Position($Bed[0],$Bed[1],$Bed[2]),Block::get(26,8 + $Bed[6]),false,true);
							 $this->Level->setBlock(new Position($Bed[3],$Bed[4],$Bed[5]),Block::get(26,    $Bed[6]),false,true);
							 };
							 foreach ($this->LevelData["spawners"] as $i => $spawner) { $spawner = explode(" ",$spawner); $type = $spawner[0]; $x = $spawner[1]; $y = $spawner[2];
							 $z = $spawner[3];
							 $pos = new Vector3($x,$y,$z);
							 switch ($this->plugin->spawner_mode) {
								 case 0:
								 $this->Level->setBlock($pos,Block::get(0,0),true,true);
								 break;
								 case 1:
								 $this->Level->setBlock($pos,Block::get(54,0),true,true);
								 $chest = new Chest($this->Level->getChunk($pos->getX() >> 4,$pos->getZ() >> 4,true),new Compound(false,array( new Int("x",$pos->getX()), new Int("y",$pos->getY()), new Int("z",$pos->getZ()), new String("id",Tile::CHEST))));
								 $this->Level->addTile($chest);
								 break;
								 };
								 };
								 foreach ($this->Level->getEntities() as $Entity) if ($Entity instanceof Villager) {
									 for ($i = 0; $i < 10; $i++) {
										 $X = round($Entity->getX() - 0.5);
										 $Z = round($Entity->getZ() - 0.5);
										 if ($this->Level->getBlockIdAt($X,0,$Z) != 54) {
										 $this->Level->setBlock(new Vector3($X,$i,$Z),Block::get(54),true,true);
										 $chest = new Chest($this->Level->getChunk($X >> 4,$Z >> 4,true),new Compound(false,array( new Int("x",$X), new Int("y",$i), new Int("z",$Z), new String("id",Tile::CHEST))),$this->plugin);
										 $this->Level->addTile($chest);
										 };
										 if ($this->Level->getBlockIdAt($X,$i + 1,$Z) != 54) $this->Level->setBlock(new Vector3($X,$i + 1,$Z),Block::get(0),true,true);
										 };
										 };
										 }
										 public function PlaceBlock(Block $Block,Player $Placer) {
											 if (in_array($Block->getId(),[ 51 ])) return true;
											 $this->BlocksPlaced[] = new Vector3($Block->getX(),$Block->getY(),$Block->getZ());
											 return false;
											 }
											 public function DestroyBlock(Block $Block,Player $Destroyer) {
												 $X = $Block->GetX(); $Y = $Block->GetY(); $Z = $Block->GetZ();
												 if ($Team = $this->getTeamByPlayer($Destroyer)) {
