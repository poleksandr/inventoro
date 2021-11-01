<?php

class ProductController
{
    private $config;
    private $noq_dir;
    private $cache_dir;

    public function __construct() {
        $this->config = include('config.php');
        $this->noq_dir = $this->config['noqFileName'] . '.txt';
        $this->cache_dir = 'cache/' . $this->config['cacheFileName'] . '.txt';
        $this->initiliazeCache();
    }

    /**
    * @param string $id
    * @return string
    */
    public function detail($id)
    {

        $product = $this->getProductsFromCache($id);

        if (!$product) {
            if ($this->config['database'] === 'elasticSearch') {
                $product = IElasticSearchDriver->findById($id);
            } else {
                $product = IMySQLDriver->findProduct($id);
            }

            $this->addProductToCache($product);
        }
        $this->incrementNoq($id);
        return json_encode($product);
    }

    /**
    * @param array $products
    */
    private function addProductToCache($product)
    {
        $products = $this->getProductsFromCache();
        $products->{$id} = $product;
        file_put_contents($this->cache_dir, serialize(json_encode($products)));
    }

    /**
    * @param string $id
    */
    public function getProductsFromCache($id = '')
    {
        $data = unserialize(file_get_contents($this->cache_dir));
        if ($data && $id !== '') {
            return json_decode($data)->{$id};
        } else {
            return $data;
        }
    }

    /**
    * @param string $id
    */
    public function getNoqs($id = '')
    {
        $data = unserialize(file_get_contents($this->noq_dir));
        if ($data && $id !== '') {
            return json_decode($data)->{$id};
        } else {
            return $data;
        }
    }

    /**
    * @param string $id
    */
    private function incrementNoq($id)
    {
        $noqs = json_decode($this->getNoqs());
        $noqs->{$id} = $noqs->{$id} + 1; 
        file_put_contents($this->noq_dir, serialize(json_encode($noqs)));
    }

    private function initiliazeCache() {
        if ($this->config['database'] === 'elasticSearch') {
            $products = IElasticSearchDriver->getAll();
        } else {
            $products = IMySQLDriver->findAll();
        }

        file_put_contents($this->cache_dir, serialize(json_encode($products)));
    }
}