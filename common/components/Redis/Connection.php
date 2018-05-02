<?php
namespace common\components\Redis;

use Predis\Client;
use Predis\ClientInterface;
use yii\base\Component;

/**
 * Interface defining a client able to execute commands against Redis.
 *
 * All the commands exposed by the client generally have the same signature as
 * described by the Redis documentation, but some of them offer an additional
 * and more friendly interface to ease programming which is described in the
 * following list of methods:
 *
 * @method int    del(array $keys)
 * @method string dump($key)
 * @method int    exists($key)
 * @method int    expire($key, $seconds)
 * @method int    expireat($key, $timestamp)
 * @method array  keys($pattern)
 * @method int    move($key, $db)
 * @method mixed  object($subcommand, $key)
 * @method int    persist($key)
 * @method int    pexpire($key, $milliseconds)
 * @method int    pexpireat($key, $timestamp)
 * @method int    pttl($key)
 * @method string randomkey()
 * @method mixed  rename($key, $target)
 * @method int    renamenx($key, $target)
 * @method array  scan($cursor, array $options = null)
 * @method array  sort($key, array $options = null)
 * @method int    ttl($key)
 * @method mixed  type($key)
 * @method int    append($key, $value)
 * @method int    bitcount($key, $start = null, $end = null)
 * @method int    bitop($operation, $destkey, $key)
 * @method int    decr($key)
 * @method int    decrby($key, $decrement)
 * @method string get($key)
 * @method int    getbit($key, $offset)
 * @method string getrange($key, $start, $end)
 * @method string getset($key, $value)
 * @method int    incr($key)
 * @method int    incrby($key, $increment)
 * @method string incrbyfloat($key, $increment)
 * @method array  mget(array $keys)
 * @method mixed  mset(array $dictionary)
 * @method int    msetnx(array $dictionary)
 * @method mixed  psetex($key, $milliseconds, $value)
 * @method mixed  set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
 * @method int    setbit($key, $offset, $value)
 * @method int    setex($key, $seconds, $value)
 * @method int    setnx($key, $value)
 * @method int    setrange($key, $offset, $value)
 * @method int    strlen($key)
 * @method int    hdel($key, array $fields)
 * @method int    hexists($key, $field)
 * @method string hget($key, $field)
 * @method array  hgetall($key)
 * @method int    hincrby($key, $field, $increment)
 * @method string hincrbyfloat($key, $field, $increment)
 * @method array  hkeys($key)
 * @method int    hlen($key)
 * @method array  hmget($key, array $fields)
 * @method mixed  hmset($key, array $dictionary)
 * @method array  hscan($key, $cursor, array $options = null)
 * @method int    hset($key, $field, $value)
 * @method int    hsetnx($key, $field, $value)
 * @method array  hvals($key)
 * @method array  blpop(array $keys, $timeout)
 * @method array  brpop(array $keys, $timeout)
 * @method array  brpoplpush($source, $destination, $timeout)
 * @method string lindex($key, $index)
 * @method int    linsert($key, $whence, $pivot, $value)
 * @method int    llen($key)
 * @method string lpop($key)
 * @method int    lpush($key, array $values)
 * @method int    lpushx($key, $value)
 * @method array  lrange($key, $start, $stop)
 * @method int    lrem($key, $count, $value)
 * @method mixed  lset($key, $index, $value)
 * @method mixed  ltrim($key, $start, $stop)
 * @method string rpop($key)
 * @method string rpoplpush($source, $destination)
 * @method int    rpush($key, array $values)
 * @method int    rpushx($key, $value)
 * @method int    sadd($key, array $members)
 * @method int    scard($key)
 * @method array  sdiff(array $keys)
 * @method int    sdiffstore($destination, array $keys)
 * @method array  sinter(array $keys)
 * @method int    sinterstore($destination, array $keys)
 * @method int    sismember($key, $member)
 * @method array  smembers($key)
 * @method int    smove($source, $destination, $member)
 * @method string spop($key)
 * @method string srandmember($key, $count = null)
 * @method int    srem($key, $member)
 * @method array  sscan($key, $cursor, array $options = null)
 * @method array  sunion(array $keys)
 * @method int    sunionstore($destination, array $keys)
 * @method int    zadd($key, array $membersAndScoresDictionary)
 * @method int    zcard($key)
 * @method string zcount($key, $min, $max)
 * @method string zincrby($key, $increment, $member)
 * @method int    zinterstore($destination, array $keys, array $options = null)
 * @method array  zrange($key, $start, $stop, array $options = null)
 * @method array  zrangebyscore($key, $min, $max, array $options = null)
 * @method int    zrank($key, $member)
 * @method int    zrem($key, $member)
 * @method int    zremrangebyrank($key, $start, $stop)
 * @method int    zremrangebyscore($key, $min, $max)
 * @method array  zrevrange($key, $start, $stop, array $options = null)
 * @method array  zrevrangebyscore($key, $min, $max, array $options = null)
 * @method int    zrevrank($key, $member)
 * @method int    zunionstore($destination, array $keys, array $options = null)
 * @method string zscore($key, $member)
 * @method array  zscan($key, $cursor, array $options = null)
 * @method array  zrangebylex($key, $start, $stop, array $options = null)
 * @method int    zremrangebylex($key, $min, $max)
 * @method int    zlexcount($key, $min, $max)
 * @method int    pfadd($key, array $elements)
 * @method mixed  pfmerge($destinationKey, array $sourceKeys)
 * @method int    pfcount(array $keys)
 * @method mixed  pubsub($subcommand, $argument)
 * @method int    publish($channel, $message)
 * @method mixed  discard()
 * @method array  exec()
 * @method mixed  multi()
 * @method mixed  unwatch()
 * @method mixed  watch($key)
 * @method mixed  eval($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method mixed  evalsha($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method mixed  script($subcommand, $argument = null)
 * @method mixed  auth($password)
 * @method string echo($message)
 * @method mixed  ping($message = null)
 * @method mixed  select($database)
 * @method mixed  bgrewriteaof()
 * @method mixed  bgsave()
 * @method mixed  client($subcommand, $argument = null)
 * @method mixed  config($subcommand, $argument = null)
 * @method int    dbsize()
 * @method mixed  flushall()
 * @method mixed  flushdb()
 * @method array  info($section = null)
 * @method int    lastsave()
 * @method mixed  save()
 * @method mixed  slaveof($host, $port)
 * @method mixed  slowlog($subcommand, $argument = null)
 * @method array  time()
 * @method array  command()
 */
class Connection extends Component implements ConnectionInterface {

    /**
     * @var array List of available redis commands http://redis.io/commands
     */
    public const REDIS_COMMANDS = [
        'BLPOP', // key [key ...] timeout Remove and get the first element in a list, or block until one is available
        'BRPOP', // key [key ...] timeout Remove and get the last element in a list, or block until one is available
        'BRPOPLPUSH', // source destination timeout Pop a value from a list, push it to another list and return it; or block until one is available
        'CLIENT KILL', // ip:port Kill the connection of a client
        'CLIENT LIST', // Get the list of client connections
        'CLIENT GETNAME', // Get the current connection name
        'CLIENT SETNAME', // connection-name Set the current connection name
        'CONFIG GET', // parameter Get the value of a configuration parameter
        'CONFIG SET', // parameter value Set a configuration parameter to the given value
        'CONFIG RESETSTAT', // Reset the stats returned by INFO
        'DBSIZE', // Return the number of keys in the selected database
        'DEBUG OBJECT', // key Get debugging information about a key
        'DEBUG SEGFAULT', // Make the server crash
        'DECR', // key Decrement the integer value of a key by one
        'DECRBY', // key decrement Decrement the integer value of a key by the given number
        'DEL', // key [key ...] Delete a key
        'DISCARD', // Discard all commands issued after MULTI
        'DUMP', // key Return a serialized version of the value stored at the specified key.
        'ECHO', // message Echo the given string
        'EVAL', // script numkeys key [key ...] arg [arg ...] Execute a Lua script server side
        'EVALSHA', // sha1 numkeys key [key ...] arg [arg ...] Execute a Lua script server side
        'EXEC', // Execute all commands issued after MULTI
        'EXISTS', // key Determine if a key exists
        'EXPIRE', // key seconds Set a key's time to live in seconds
        'EXPIREAT', // key timestamp Set the expiration for a key as a UNIX timestamp
        'FLUSHALL', // Remove all keys from all databases
        'FLUSHDB', // Remove all keys from the current database
        'GET', // key Get the value of a key
        'GETBIT', // key offset Returns the bit value at offset in the string value stored at key
        'GETRANGE', // key start end Get a substring of the string stored at a key
        'GETSET', // key value Set the string value of a key and return its old value
        'HDEL', // key field [field ...] Delete one or more hash fields
        'HEXISTS', // key field Determine if a hash field exists
        'HGET', // key field Get the value of a hash field
        'HGETALL', // key Get all the fields and values in a hash
        'HINCRBY', // key field increment Increment the integer value of a hash field by the given number
        'HINCRBYFLOAT', // key field increment Increment the float value of a hash field by the given amount
        'HKEYS', // key Get all the fields in a hash
        'HLEN', // key Get the number of fields in a hash
        'HMGET', // key field [field ...] Get the values of all the given hash fields
        'HMSET', // key field value [field value ...] Set multiple hash fields to multiple values
        'HSET', // key field value Set the string value of a hash field
        'HSETNX', // key field value Set the value of a hash field, only if the field does not exist
        'HVALS', // key Get all the values in a hash
        'INCR', // key Increment the integer value of a key by one
        'INCRBY', // key increment Increment the integer value of a key by the given amount
        'INCRBYFLOAT', // key increment Increment the float value of a key by the given amount
        'INFO', // [section] Get information and statistics about the server
        'KEYS', // pattern Find all keys matching the given pattern
        'LASTSAVE', // Get the UNIX time stamp of the last successful save to disk
        'LINDEX', // key index Get an element from a list by its index
        'LINSERT', // key BEFORE|AFTER pivot value Insert an element before or after another element in a list
        'LLEN', // key Get the length of a list
        'LPOP', // key Remove and get the first element in a list
        'LPUSH', // key value [value ...] Prepend one or multiple values to a list
        'LPUSHX', // key value Prepend a value to a list, only if the list exists
        'LRANGE', // key start stop Get a range of elements from a list
        'LREM', // key count value Remove elements from a list
        'LSET', // key index value Set the value of an element in a list by its index
        'LTRIM', // key start stop Trim a list to the specified range
        'MGET', // key [key ...] Get the values of all the given keys
        'MIGRATE', // host port key destination-db timeout Atomically transfer a key from a Redis instance to another one.
        'MONITOR', // Listen for all requests received by the server in real time
        'MOVE', // key db Move a key to another database
        'MSET', // key value [key value ...] Set multiple keys to multiple values
        'MSETNX', // key value [key value ...] Set multiple keys to multiple values, only if none of the keys exist
        'MULTI', // Mark the start of a transaction block
        'OBJECT', // subcommand [arguments [arguments ...]] Inspect the internals of Redis objects
        'PERSIST', // key Remove the expiration from a key
        'PEXPIRE', // key milliseconds Set a key's time to live in milliseconds
        'PEXPIREAT', // key milliseconds-timestamp Set the expiration for a key as a UNIX timestamp specified in milliseconds
        'PING', // Ping the server
        'PSETEX', // key milliseconds value Set the value and expiration in milliseconds of a key
        'PSUBSCRIBE', // pattern [pattern ...] Listen for messages published to channels matching the given patterns
        'PTTL', // key Get the time to live for a key in milliseconds
        'PUBLISH', // channel message Post a message to a channel
        'PUNSUBSCRIBE', // [pattern [pattern ...]] Stop listening for messages posted to channels matching the given patterns
        'QUIT', // Close the connection
        'RANDOMKEY', // Return a random key from the keyspace
        'RENAME', // key newkey Rename a key
        'RENAMENX', // key newkey Rename a key, only if the new key does not exist
        'RESTORE', // key ttl serialized-value Create a key using the provided serialized value, previously obtained using DUMP.
        'RPOP', // key Remove and get the last element in a list
        'RPOPLPUSH', // source destination Remove the last element in a list, append it to another list and return it
        'RPUSH', // key value [value ...] Append one or multiple values to a list
        'RPUSHX', // key value Append a value to a list, only if the list exists
        'SADD', // key member [member ...] Add one or more members to a set
        'SAVE', // Synchronously save the dataset to disk
        'SCARD', // key Get the number of members in a set
        'SCRIPT EXISTS', // script [script ...] Check existence of scripts in the script cache.
        'SCRIPT FLUSH', // Remove all the scripts from the script cache.
        'SCRIPT KILL', // Kill the script currently in execution.
        'SCRIPT LOAD', // script Load the specified Lua script into the script cache.
        'SDIFF', // key [key ...] Subtract multiple sets
        'SDIFFSTORE', // destination key [key ...] Subtract multiple sets and store the resulting set in a key
        'SELECT', // index Change the selected database for the current connection
        'SET', // key value Set the string value of a key
        'SETBIT', // key offset value Sets or clears the bit at offset in the string value stored at key
        'SETEX', // key seconds value Set the value and expiration of a key
        'SETNX', // key value Set the value of a key, only if the key does not exist
        'SETRANGE', // key offset value Overwrite part of a string at key starting at the specified offset
        'SHUTDOWN', // [NOSAVE] [SAVE] Synchronously save the dataset to disk and then shut down the server
        'SINTER', // key [key ...] Intersect multiple sets
        'SINTERSTORE', // destination key [key ...] Intersect multiple sets and store the resulting set in a key
        'SISMEMBER', // key member Determine if a given value is a member of a set
        'SLAVEOF', // host port Make the server a slave of another instance, or promote it as master
        'SLOWLOG', // subcommand [argument] Manages the Redis slow queries log
        'SMEMBERS', // key Get all the members in a set
        'SMOVE', // source destination member Move a member from one set to another
        'SORT', // key [BY pattern] [LIMIT offset count] [GET pattern [GET pattern ...]] [ASC|DESC] [ALPHA] [STORE destination] Sort the elements in a list, set or sorted set
        'SPOP', // key Remove and return a random member from a set
        'SRANDMEMBER', // key [count] Get one or multiple random members from a set
        'SREM', // key member [member ...] Remove one or more members from a set
        'STRLEN', // key Get the length of the value stored in a key
        'SUBSCRIBE', // channel [channel ...] Listen for messages published to the given channels
        'SUNION', // key [key ...] Add multiple sets
        'SUNIONSTORE', // destination key [key ...] Add multiple sets and store the resulting set in a key
        'SYNC', // Internal command used for replication
        'TIME', // Return the current server time
        'TTL', // key Get the time to live for a key
        'TYPE', // key Determine the type stored at key
        'UNSUBSCRIBE', // [channel [channel ...]] Stop listening for messages posted to the given channels
        'UNWATCH', // Forget about all watched keys
        'WATCH', // key [key ...] Watch the given keys to determine execution of the MULTI/EXEC block
        'ZADD', // key score member [score member ...] Add one or more members to a sorted set, or update its score if it already exists
        'ZCARD', // key Get the number of members in a sorted set
        'ZCOUNT', // key min max Count the members in a sorted set with scores within the given values
        'ZINCRBY', // key increment member Increment the score of a member in a sorted set
        'ZINTERSTORE', // destination numkeys key [key ...] [WEIGHTS weight [weight ...]] [AGGREGATE SUM|MIN|MAX] Intersect multiple sorted sets and store the resulting sorted set in a new key
        'ZRANGE', // key start stop [WITHSCORES] Return a range of members in a sorted set, by index
        'ZRANGEBYSCORE', // key min max [WITHSCORES] [LIMIT offset count] Return a range of members in a sorted set, by score
        'ZRANK', // key member Determine the index of a member in a sorted set
        'ZREM', // key member [member ...] Remove one or more members from a sorted set
        'ZREMRANGEBYRANK', // key start stop Remove all members in a sorted set within the given indexes
        'ZREMRANGEBYSCORE', // key min max Remove all members in a sorted set within the given scores
        'ZREVRANGE', // key start stop [WITHSCORES] Return a range of members in a sorted set, by index, with scores ordered from high to low
        'ZREVRANGEBYSCORE', // key max min [WITHSCORES] [LIMIT offset count] Return a range of members in a sorted set, by score, with scores ordered from high to low
        'ZREVRANK', // key member Determine the index of a member in a sorted set, with scores ordered from high to low
        'ZSCORE', // key member Get the score associated with the given member in a sorted set
        'ZUNIONSTORE', // destination numkeys key [key ...] [WEIGHTS weight [weight ...]] [AGGREGATE SUM|MIN|MAX] Add multiple sorted sets and store the resulting sorted set in a new key
        'GEOADD', // key longitude latitude member [longitude latitude member ...] Add point
        'GEODIST', // key member1 member2 [unit] Return the distance between two members
        'GEOHASH', // key member [member ...] Return valid Geohash strings
        'GEOPOS', // key member [member ...] Return the positions (longitude,latitude)
        'GEORADIUS', // key longitude latitude radius m|km|ft|mi [WITHCOORD] [WITHDIST] [WITHHASH] [COUNT count] Return the members
        'GEORADIUSBYMEMBER', // key member radius m|km|ft|mi [WITHCOORD] [WITHDIST] [WITHHASH] [COUNT count]
    ];

    /**
     * @var string the hostname or ip address to use for connecting to the redis server. Defaults to 'localhost'.
     * If [[unixSocket]] is specified, hostname and port will be ignored.
     */
    public $hostname = 'localhost';

    /**
     * @var integer the port to use for connecting to the redis server. Default port is 6379.
     * If [[unixSocket]] is specified, hostname and port will be ignored.
     */
    public $port = 6379;

    /**
     * @var string the unix socket path (e.g. `/var/run/redis/redis.sock`) to use for connecting to the redis server.
     * This can be used instead of [[hostname]] and [[port]] to connect to the server using a unix socket.
     * If a unix socket path is specified, [[hostname]] and [[port]] will be ignored.
     */
    public $unixSocket;

    /**
     * @var string the password for establishing DB connection. Defaults to null meaning no AUTH command is send.
     * See http://redis.io/commands/auth
     */
    public $password;

    /**
     * @var integer the redis database to use. This is an integer value starting from 0. Defaults to 0.
     */
    public $database = 0;

    /**
     * @var float timeout to use for connection to redis. If not set the timeout set in php.ini will be used: ini_get("default_socket_timeout")
     */
    public $connectionTimeout;

    /**
     * @var float timeout to use for redis socket when reading and writing data. If not set the php default value will be used.
     */
    public $dataTimeout;

    /**
     * @var integer Bitmask field which may be set to any combination of connection flags passed to [stream_socket_client()](http://php.net/manual/en/function.stream-socket-client.php).
     * Currently the select of connection flags is limited to `STREAM_CLIENT_CONNECT` (default), `STREAM_CLIENT_ASYNC_CONNECT` and `STREAM_CLIENT_PERSISTENT`.
     * @see http://php.net/manual/en/function.stream-socket-client.php
     */
    public $socketClientFlags = STREAM_CLIENT_CONNECT;

    /**
     * @var array|null
     */
    public $parameters;

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var ClientInterface
     */
    private $_client;

    public function __call($name, $params) {
        $redisCommand = mb_strtoupper($name);
        if (in_array($redisCommand, self::REDIS_COMMANDS)) {
            return $this->executeCommand($name, $params);
        }

        return parent::__call($name, $params);
    }

    public function getConnection(): ClientInterface {
        if ($this->_client === null) {
            $this->_client = new Client($this->prepareParams(), $this->options);
        }

        return $this->_client;
    }

    public function executeCommand(string $name, array $params = []) {
        return $this->getConnection()->$name(...$params);
    }

    private function prepareParams() {
        if ($this->parameters !== null) {
            return $this->parameters;
        }

        if ($this->unixSocket) {
            $parameters = [
                'scheme' => 'unix',
                'path' => $this->unixSocket,
            ];
        } else {
            $parameters = [
                'scheme' => 'tcp',
                'host' => $this->hostname,
                'port' => $this->port,
            ];
        }

        return array_merge($parameters, [
            'database' => $this->database,
        ]);
    }

}
