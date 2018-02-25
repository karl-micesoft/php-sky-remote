<?php

namespace PhpSkyRemote;
use Exception;

/**
 * Description of PhpSkyRemote
 *
 * @author Karl
 */
class PhpSkyRemote {
    const PORT_DEFAULT = 49160;
    const PORT_SKY_Q_LEGACY = 5900;
    
    const COMMAND_0 = 48;
    const COMMAND_1 = 49;
    const COMMAND_2 = 50;
    const COMMAND_3 = 51;
    const COMMAND_4 = 52;
    const COMMAND_5 = 53;
    const COMMAND_6 = 54;
    const COMMAND_7 = 55;
    const COMMAND_8 = 56;
    const COMMAND_9 = 57;
    const COMMAND_POWER = 0;
    const COMMAND_SELECT = 1;
    const COMMAND_BACKUP = 2;
    const COMMAND_DISMISS = 2;
    const COMMAND_CHANNELUP = 6;
    const COMMAND_CHANNELDOWN = 7;
    const COMMAND_INTERACTIVE = 8;
    const COMMAND_SIDEBAR = 8;
    const COMMAND_HELP = 9;
    const COMMAND_SERVICES = 10;
    const COMMAND_SEARCH = 10;
    const COMMAND_TVGUIDE = 11;
    const COMMAND_HOME = 11;
    const COMMAND_I = 14;
    const COMMAND_TEXT = 15;
    const COMMAND_UP = 16;
    const COMMAND_DOWN = 17;
    const COMMAND_LEFT = 18;
    const COMMAND_RIGHT = 19;
    const COMMAND_RED = 32;
    const COMMAND_GREEN = 33;
    const COMMAND_YELLOW = 34;
    const COMMAND_BLUE = 35;
    const COMMAND_PLAY = 64;
    const COMMAND_PAUSE = 65;
    const COMMAND_STOP = 66;
    const COMMAND_RECORD = 67;
    const COMMAND_FASTFORWARD = 69;
    const COMMAND_REWIND = 71;
    const COMMAND_BOXOFFICE = 240;
    const COMMAND_SKY = 241;

    protected static $codes = null;
    
    protected static $validCodes = null;

    protected $ip, $port;
    
    public function __construct($ip, $port = null) {
        if(!$port) {
            $port = static::PORT_DEFAULT;
        }
        $this->setIp($ip);
        $this->port = $port;
    }
    
    public function setIp($ip) {
        $pattern = '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/';
        if(preg_match($pattern, $ip)) {
            $nums = explode('.', $ip);
            foreach($nums as $n) {
                if($n > 255) {
                    throw new Exception("Invalid IP address $ip");
                }
            }
        } else {
            // assume hostname
            $hostname = $ip;
            $ip = gethostbyname($hostname);
            if($hostname == $ip) {
                throw new Exception("Can't find host $hostname");
            }
        }
        $this->ip = $ip;
    }
    
    public function press($sequence) {
        if(is_array($sequence)) {
            foreach($sequence as $cmd) {
                $this->press($cmd);
                usleep(500000); // 0.2s
            }
            return;
        }
        if(is_string($sequence)) {
            try {
                return $this->press($this->getCode($sequence));
            } catch (Exception $ex) {
                if(strstr($sequence, '[')) {
                    // Try JSON first
                    $data = json_decode($sequence, true);
                    if(is_array($data)) {
                        return $this->press($data);
                    }
                }
                if(strstr($sequence, ' ')) {
                    return $this->press(explode(' ', $sequence));
                }
                if(strstr($sequence, ',')) {
                    return $this->press(explode(',', $sequence));
                }
                if(strstr($sequence, '|')) {
                    return $this->press(explode('|', $sequence));
                }
            }
        }
        
        if(is_int($sequence)) {
            return $this->sendCode($sequence);
        }
        
        throw new Exception("Invalid sequence");
    }
    
    public function sendCode($code) {
        if(!static::isValidCode($code)) {
            throw new Exception("Invalid code: $code");
        }
        
        $socket = $this->getSocket();
        
        // Wait until ready for data
        $l = 12;
	
	while($data = unpack('C*', fread($socket, 24))) {
            if(count($data) < 24) {
                    fwrite($socket, pack('C*', ...$data), $l);
                    $l = 1;
            } else {
                    break;
            }
	}
        
        // Write first packet
        fwrite($socket, static::getPacket($code, false));
        
        // Write second packet
        fwrite($socket, static::getPacket($code, true));
        
        // Close
        fclose($socket);
    }
    
    protected function getSocket() {
        $socket = stream_socket_client("tcp://{$this->ip}:{$this->port}", $errNo, $errMsg);

	if($socket === false) {
		throw new Exception("Failed to connect: [$errNo] $errMsg");
	}
        
        return $socket;
    }
    
    protected static function populateCodes() {
        if(null === static::$codes) {
            static::$codes = [
                    '0' => static::COMMAND_0,
                    '1' => static::COMMAND_1,
                    '2' => static::COMMAND_2,
                    '3' => static::COMMAND_3,
                    '4' => static::COMMAND_4,
                    '5' => static::COMMAND_5,
                    '6' => static::COMMAND_6,
                    '7' => static::COMMAND_7,
                    '8' => static::COMMAND_8,
                    '9' => static::COMMAND_9,
                    'power' => static::COMMAND_POWER,
                    'select' => static::COMMAND_SELECT,
                    'backup' => static::COMMAND_BACKUP,
                    'dismiss' => static::COMMAND_DISMISS,
                    'channelup' => static::COMMAND_CHANNELUP,
                    'channeldown' => static::COMMAND_CHANNELDOWN,
                    'interactive' => static::COMMAND_INTERACTIVE,
                    'sidebar' => static::COMMAND_SIDEBAR,
                    'help' => static::COMMAND_HELP,
                    'services' => static::COMMAND_SERVICES,
                    'search' => static::COMMAND_SEARCH,
                    'tvguide' => static::COMMAND_TVGUIDE,
                    'home' => static::COMMAND_HOME,
                    'i' => static::COMMAND_I,
                    'text' => static::COMMAND_TEXT,
                    'up' => static::COMMAND_UP,
                    'down' => static::COMMAND_DOWN,
                    'left' => static::COMMAND_LEFT,
                    'right' => static::COMMAND_RIGHT,
                    'red' => static::COMMAND_RED,
                    'green' => static::COMMAND_GREEN,
                    'yellow' => static::COMMAND_YELLOW,
                    'blue' => static::COMMAND_BLUE,
                    'play' => static::COMMAND_PLAY,
                    'pause' => static::COMMAND_PAUSE,
                    'stop' => static::COMMAND_STOP,
                    'record' => static::COMMAND_RECORD,
                    'fastforward' => static::COMMAND_FASTFORWARD,
                    'rewind' => static::COMMAND_REWIND,
                    'boxoffice' => static::COMMAND_BOXOFFICE,
                    'sky' => static::COMMAND_SKY,
                
                    // Sequence
                    'tvguideall' => [
                        static::COMMAND_HOME,
                        static::COMMAND_UP,
                        static::COMMAND_SELECT,
                        static::COMMAND_SELECT,
                    ]
            ];
            
            $searchCharMap = [
                static::COMMAND_1 => ['1'],
                static::COMMAND_2 => ['a', 'b', 'c', '2'],
                static::COMMAND_3 => ['d', 'e', 'f', '3'],
                static::COMMAND_4 => ['g', 'h', 'i', '4'],
                static::COMMAND_5 => ['j', 'k', 'l', '5'],
                static::COMMAND_6 => ['m', 'n', 'o', '6'],
                static::COMMAND_7 => ['p', 'q', 'r', 's', '7'],
                static::COMMAND_8 => ['t', 'u', 'v', '8'],
                static::COMMAND_9 => ['w', 'x', 'y', 'z', '9'],
                static::COMMAND_0 => ['space', '0'],
            ];
            
            foreach($searchCharMap as $buttonCode => $chars) {
                static::$codes = array_merge(
                    static::$codes,
                    static::generateSearchCodeSequences($chars, $buttonCode)
                );
            }
            
            static::$validCodes = array_filter(
                    array_values(static::$codes),
                    function($val) {
                        return is_int($val);
                    }
            );
        }
    }
    
    protected static function generateSearchCodeSequences($chars, $buttonCode) {
        $codes = [];
        $current = [];
        foreach($chars as $char) {
            $current[] = $buttonCode;
            $codes['search_' . $char] = $current;
        }
        return $codes;
    }
    
    protected static function getCode($command) {
        static::populateCodes();
        if(isset(static::$codes[$command])) {
            return static::$codes[$command];
        } else {
            throw new Exception("Invalid command: $command");
        }
    }
    
    protected static function isValidCode($code) {
        static::populateCodes();
        return in_array($code, static::$validCodes);
    }
    
    protected static function getPacket($code, $isSecond = false) {
        $data = [
                4,
		1,
		0,
		0,
		0,
		0,
		224 + ($code>>4),
		$code&15
        ];
        
        if($isSecond) {
            $data[1] = 0;
        }
        
        return pack('C*', ...$data);
    }
    
    
}
