<?php

namespace Elastica;
use Elastica\Exception\InvalidException;
use Elastica\Exception\NotImplementedException;

/**
 * Elastica search object
 *
 * @category Xodoa
 * @package  Elastica
 * @author   Nicolas Ruflin <spam@ruflin.com>
 */
class Search implements SearchableInterface
{
    /*
     * Options
     */
    const OPTION_SEARCH_TYPE = 'search_type';
    const OPTION_ROUTING = 'routing';
    const OPTION_PREFERENCE = 'preference';
    const OPTION_VERSION = 'version';
    const OPTION_TIMEOUT = 'timeout';
    const OPTION_FROM = 'from';
    const OPTION_SIZE = 'size';

    /*
     * Search types
     */
    const OPTION_SEARCH_TYPE_COUNT = 'count';
    const OPTION_SEARCH_TYPE_SCAN = 'scan';
    const OPTION_SEARCH_TYPE_DFS_QUERY_THEN_FETCH = 'dfs_query_then_fetch';
    const OPTION_SEARCH_TYPE_DFS_QUERY_AND_FETCH = 'dfs_query_and_fetch';
    const OPTION_SEARCH_TYPE_QUERY_THEN_FETCH = 'query_then_fetch';
    const OPTION_SEARCH_TYPE_QUERY_AND_FETCH = 'query_and_fetch';

    /**
     * Array of indices
     *
     * @var array
     */
    protected $_indices = array();

    /**
     * Array of types
     *
     * @var array
     */
    protected $_types = array();

    /**
     * @var \Elastica\Query
     */
    protected $_query;

    /**
     * @var array
     */
    protected $_options = array();

    /**
     * Client object
     *
     * @var \Elastica\Client
     */
    protected $_client;

    /**
     * Constructs search object
     *
     * @param \Elastica\Client $client Client object
     */
    public function __construct(Client $client)
    {
        $this->_client = $client;
    }

    /**
     * Adds a index to the list
     *
     * @param  \Elastica\Index|string               $index Index object or string
     * @throws \Elastica\Exception\InvalidException
     * @return \Elastica\Search                     Current object
     */
    public function addIndex($index)
    {
        if ($index instanceof Index) {
            $index = $index->getName();
        }

        if (!is_string($index)) {
            throw new InvalidException('Invalid param type');
        }

        $this->_indices[] = $index;

        return $this;
    }

    /**
     * Add array of indices at once
     *
     * @param  array           $indices
     * @return \Elastica\Search
     */
    public function addIndices(array $indices = array())
    {
        foreach ($indices as $index) {
            $this->addIndex($index);
        }

        return $this;
    }

    /**
     * Adds a type to the current search
     *
     * @param  \Elastica\Type|string                $type Type name or object
     * @return \Elastica\Search                     Search object
     * @throws \Elastica\Exception\InvalidException
     */
    public function addType($type)
    {
        if ($type instanceof Type) {
            $type = $type->getName();
        }

        if (!is_string($type)) {
            throw new InvalidException('Invalid type type');
        }

        $this->_types[] = $type;

        return $this;
    }

    /**
     * Add array of types
     *
     * @param  array           $types
     * @return \Elastica\Search
     */
    public function addTypes(array $types = array())
    {
        foreach ($types as $type) {
            $this->addType($type);
        }

        return $this;
    }

    /**
     * @param  string|array|\Elastica\Query|\Elastica\Query\AbstractQuery|\Elastica\Filter\AbstractFilter $query
     * @return \Elastica\Search
     */
    public function setQuery($query)
    {
        $this->_query = Query::create($query);

        return $this;
    }

    /**
     * @param  string          $key
     * @param  mixed           $value
     * @return \Elastica\Search
     */
    public function setOption($key, $value)
    {
        $this->_validateOption($key);

        $this->_options[$key] = $value;

        return $this;
    }

    /**
     * @param  array           $options
     * @return \Elastica\Search
     */
    public function setOptions(array $options)
    {
        $this->clearOptions();

        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }

        return $this;
    }

    /**
     * @return \Elastica\Search
     */
    public function clearOptions()
    {
        $this->_options = array();

        return $this;
    }

    /**
     * @param  string          $key
     * @param  mixed           $value
     * @return \Elastica\Search
     */
    public function addOption($key, $value)
    {
        $this->_validateOption($key);

        if (!isset($this->_options[$key])) {
            $this->_options[$key] = array();
        }

        $this->_options[$key][] = $value;

        return $this;
    }

    /**
     * @param  string $key
     * @return bool
     */
    public function hasOption($key)
    {
        return isset($this->_options[$key]);
    }

    /**
     * @param  string                              $key
     * @return mixed
     * @throws \Elastica\Exception\InvalidException
     */
    public function getOption($key)
    {
        if (!$this->hasOption($key)) {
            throw new InvalidException('Option ' . $key . ' does not exist');
        }

        return $this->_options[$key];
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * @param  string                              $key
     * @return bool
     * @throws \Elastica\Exception\InvalidException
     */
    protected function _validateOption($key)
    {
        switch ($key) {
            case self::OPTION_SEARCH_TYPE:
            case self::OPTION_ROUTING:
            case self::OPTION_PREFERENCE:
            case self::OPTION_VERSION:
            case self::OPTION_TIMEOUT:
            case self::OPTION_FROM:
            case self::OPTION_SIZE:
                return true;
        }

        throw new InvalidException('Invalid option ' . $key);
    }

    /**
     * Return client object
     *
     * @return \Elastica\Client Client object
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * Return array of indices
     *
     * @return array List of index names
     */
    public function getIndices()
    {
        return $this->_indices;
    }

    /**
     * @return bool
     */
    public function hasIndices()
    {
        return count($this->_indices) > 0;
    }

    /**
     * Return array of types
     *
     * @return array List of types
     */
    public function getTypes()
    {
        return $this->_types;
    }

    /**
     * @return bool
     */
    public function hasTypes()
    {
        return count($this->_types) > 0;
    }

    /**
     * @return \Elastica\Query
     */
    public function getQuery()
    {
        if (null === $this->_query) {
            $this->_query = Query::create('');
        }

        return $this->_query;
    }

    /**
     * Creates new search object
     *
     * @param  \Elastica\SearchableInterface               $searchObject
     * @throws \Elastica\Exception\NotImplementedException
     * @return void
     */
    public static function create(SearchableInterface $searchObject)
    {
        throw new NotImplementedException();
        // Set index
        // set type
        // set client
    }

    /**
     * Combines indices and types to the search request path
     *
     * @return string Search path
     */
    public function getPath()
    {
        $indices = $this->getIndices();

        $path = '';
        $types = $this->getTypes();

        if (empty($indices)) {
            if (!empty($types)) {
                $path .= '_all';
            }
        } else {
            $path .= implode(',', $indices);
        }

        if (!empty($types)) {
            $path .= '/' . implode(',', $types);
        }

        // Add full path based on indices and types -> could be all
        return $path . '/_search';
    }

    /**
     * Search in the set indices, types
     *
     * @param  mixed                               $query
     * @param  int|array                           $options OPTIONAL Limit or associative array of options (option=>value)
     * @throws \Elastica\Exception\InvalidException
     * @return \Elastica\ResultSet
     */
    public function search($query = '', $options = null)
    {
        $this->_setOptionsAndQuery($options, $query);

        $query = $this->getQuery();
        $path = $this->getPath();

        $params = $this->getOptions();

        $response = $this->getClient()->request(
            $path,
            Request::GET,
            $query->toArray(),
            $params
        );

        return new ResultSet($response, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function count($query = '')
    {
        $this->_setOptionsAndQuery(null, $query);

        $query = $this->getQuery();
        $path = $this->getPath();

        $response = $this->getClient()->request(
            $path,
            Request::GET,
            $query->toArray(),
            array(self::OPTION_SEARCH_TYPE => self::OPTION_SEARCH_TYPE_COUNT)
        );
        $resultSet = new ResultSet($response, $query);

        return $resultSet->getTotalHits();
    }

    /**
     * @param  array|int                   $options
     * @param  string|array|\Elastica\Query $query
     * @return \Elastica\Search
     */
    protected function _setOptionsAndQuery($options = null, $query = '')
    {
        if ('' != $query) {
            $this->setQuery($query);
        }

        if (is_int($options)) {
            $this->getQuery()->setLimit($options);
        } elseif (is_array($options)) {
            if (isset($options['limit'])) {
                $this->getQuery()->setLimit($options['limit']);
                unset($options['limit']);
            }
              if (isset($options['explain'])) {
                $this->getQuery()->setExplain($options['explain']);
                unset($options['explain']);
            }
            $this->setOptions($options);
        }

        return $this;
    }
}
