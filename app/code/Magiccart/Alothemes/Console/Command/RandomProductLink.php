<?php

/**
 * @Author: nguyen
 * @Date:   2020-12-15 14:01:01
 * @Last Modified by:   Alex Dong
 * @Last Modified time: 2023-08-19 16:25:12
 * https://github.com/magento/magento2-samples/tree/master/sample-module-command/Console/Command
 */

namespace Magiccart\Alothemes\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Console\Cli;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

class RandomProductLink extends Command
{
    /**
     * Sku argument
     */
    const SKU_ARGUMENT = 'sku';

    /**
     * LIMIT argument
     */
    const LIMIT_ARGUMENT = 'limit';

    /**
     * AttributeSet argument
     */
    const ATTRIBUTE_SET = 'attribute_set';

    /**
     * Allow option
     */
    const ALL_PRODUCT = 'all-product';

    protected $linkType = ['related', 'upsell', 'crosssell'];

    protected $commandName = 'RandomProductLink';

    protected $attributeSet;

    /** @var \Magento\Framework\App\State **/
    private $state;

    /**
     * @var ProductLinkInterfaceFactory
     */
    protected $productLinkFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * Catalog product visibility
     *
     * @var ProductVisibility
     */
    protected $productVisibility;

    /**
     *
     * @var objectManager
     */
    private $objectManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Framework\App\State $state
     * @param ProductLinkInterfaceFactory $productLinkFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductVisibility $productVisibility
     * @param string $name
     */
    public function __construct(
        \Magento\Framework\App\State $state,
        ProductLinkInterfaceFactory $productLinkFactory,
        ProductRepositoryInterface $productRepository,
        ProductCollectionFactory $productCollectionFactory,
        ProductVisibility $productVisibility,
        string $name = null,
        ?LoggerInterface $logger = null
    ) {
        $this->state = $state;
        $this->productLinkFactory = $productLinkFactory;
        $this->productRepository  = $productRepository;
        $this->productCollectionFactory  = $productCollectionFactory;
        $this->productVisibility = $productVisibility;
        $this->objectManager = ObjectManager::getInstance();
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);

        parent::__construct($name);
    }

    protected function configure()
    {
        // command: php bin/magento RandomProductLink 24-MB02-1 10
        // command: php bin/magento RandomProductLink '*' 10
        $this->setName($this->commandName)
            ->setDescription(__('Add a Random %1 product', implode(', ', $this->linkType)))
            ->setDefinition([
                new InputArgument(
                    self::SKU_ARGUMENT,
                    InputArgument::REQUIRED,
                    'Sku of Product'
                ),
                new InputArgument(
                    self::LIMIT_ARGUMENT,
                    InputArgument::REQUIRED,
                    __('Number %1 product will add', implode(', ', $this->linkType))
                ),
                new InputArgument(
                    self::ATTRIBUTE_SET,
                    InputArgument::OPTIONAL,
                    __('AttributeSet %1 product will add', implode(', ', $this->linkType))
                ),
                new InputOption(
                    self::ALL_PRODUCT,
                    '-a',
                    InputOption::VALUE_NONE,
                    'All Products includes product not Visibility'
                ),

            ]);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML); 
        $returnValue = Cli::RETURN_SUCCESS;
        try {
            $sku   = $input->getArgument(self::SKU_ARGUMENT);
            $limit = $input->getArgument(self::LIMIT_ARGUMENT);
            $this->attributeSet = $input->getArgument(self::ATTRIBUTE_SET);
            $allProduct = $input->getOption(self::ALL_PRODUCT);
            $products = $this->getProducts($sku, $allProduct);
            $productSkus = [];
            foreach ($products as $product) {
                $productSkus[$product->getSku()] = $product->getId();
            }
            $num = 0;
            foreach ($productSkus as $mainSku => $id) {
                $mainProduct = $this->productRepository->get($mainSku);
                $tmp = $productSkus;
                unset($tmp[$mainSku]);
                $linkedProducts = [];
                foreach ($this->linkType as $linkType) {
                    $linkedSku = (count($productSkus) > $limit) ? array_rand($tmp, $limit) : array_keys($tmp);
                    shuffle($linkedSku);
                    foreach ($linkedSku as $linkSku) {
                        $linkedProducts[] = $this->productLinkFactory->create()
                                                ->setSku($mainSku)
                                                ->setLinkedProductSku($linkSku)
                                                ->setLinkType($linkType);
                    }
                }
                $mainProduct->setProductLinks($linkedProducts)
                            ->setStoreId(0)
                            ->save();
                $name = $mainProduct->getName() . ' (ID: ' . $id . ')';
                $output->writeln($name);
                $num++;
            }           

            $message = __("Successfully add %1 %2 product(s)!", implode(', ', $this->linkType), $num);
            $output->writeln($message);

            $arguments = new ArrayInput(['command' => 'cache:flush']);
            $this->getApplication()->find('cache:flush')->run($arguments, $output);
        } catch (IOExceptionInterface $e) {
            $message = __("An error occurred while deleting your directory at %1", $e->getPath());
            $output->writeln($message);
            $output->writeln($e->getMessage());
            $returnValue = Cli::RETURN_FAILURE;

            $this->logger->critical($e->getMessage());
        }

        return $returnValue;
    }

    public function getProducts($skuFilter, $allProduct=false)
    {
        if ($skuFilter == '*') {
            $skuFilter = '';
        } elseif (str_contains($skuFilter, '%')) {
            $skuFilter = ['like' => $skuFilter];
        } elseif (str_contains($skuFilter, ',')) {
            $skuFilter = explode(',', $skuFilter);
        }
        $collection = $this->productCollectionFactory->create()->setStoreId(0)->addAttributeToSelect('name');

        if ($skuFilter) {
            $collection->addAttributeToSelect('sku')
                        ->addAttributeToFilter('sku', $skuFilter);
        }
        if(!$allProduct){
            $collection->addAttributeToFilter('type_id', ['neq' => 'downloadable'])
                    ->addAttributeToFilter('status', ProductStatus::STATUS_ENABLED)
                    ->setVisibility($this->productVisibility->getVisibleInCatalogIds());
        }
        if($this->attributeSet){
            $attributeSetCollection  = $this->objectManager->get('\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory')->create()
                                                            ->addFieldToSelect('attribute_set_id')
                                                            ->addFieldToFilter('attribute_set_name', $this->attributeSet);
            
            $attributeSetIds = [];
            foreach ($attributeSetCollection as $attributeSet) {
                $attributeSetIds[] = $attributeSet->getData('attribute_set_id');
            }
            if(!empty($attributeSetIds)){
                // $collection->addAttributeToFilter('attribute_set_id', ['in' => [4, 10]]);
                $collection->addAttributeToFilter('attribute_set_id', ['in' => $attributeSetIds]);
            }
        }

        return $collection;
    }
}