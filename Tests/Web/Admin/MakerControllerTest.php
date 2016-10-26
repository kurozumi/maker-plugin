<?php
/*
 * This file is part of the Maker plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Maker\Tests\Web\Admin;

use Faker\Generator;
use Plugin\Maker\Tests\Web\MakerWebCommon;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class MakerControllerTest.
 */
class MakerControllerTest extends MakerWebCommon
{
    /**
     * Set up function.
     */
    public function setUp()
    {
        parent::setUp();
        $this->deleteAllRows(array('plg_product_maker', 'plg_maker'));
    }

    /**
     * Test render maker.
     */
    public function testMakerRender()
    {
        $crawler = $this->client->request('GET', $this->app->url('admin_maker'));
        $this->assertContains('データはありません', $crawler->filter('.box')->html());
    }

    /**
     * Test maker list
     */
    public function testMakerList()
    {
        $numberTest = 100;
        for ($i = 1; $i <= $numberTest; ++$i) {
            $this->createMaker($i);
        }

        $crawler = $this->client->request('GET', $this->app->url('admin_maker'));
        $number = count($crawler->filter('.tableish .item_box'));

        $this->actual = $number;
        $this->expected = $numberTest;
        $this->verify();
    }

    /**
     * Test maker create.
     */
    public function testMakerCreateNameIsEmpty()
    {
        $formData = $this->createFormData();
        $formData['name'] = '';
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_maker'),
            array('admin_maker' => $formData)
        );

        // Check message
        $this->assertContains('※ メーカー名が入力されていません。', $crawler->filter('.modal-dialog .attention')->html());
    }

    /**
     * Test maker create.
     */
    public function testMakerCreateNameIsDuplicate()
    {
        // Exist maker
        $Maker = $this->createMaker(1);
        $formData = $this->createFormData();
        $formData['name'] = $Maker->getName();
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_maker'),
            array('admin_maker' => $formData)
        );

        // Check message
        $this->assertContains('※ 既に使用されています。', $crawler->filter('.modal-dialog .attention')->html());
    }

    /**
     * Test maker create.
     */
    public function testMakerCreate()
    {
        $formData = $this->createFormData();
        $this->client->request(
            'POST',
            $this->app->url('admin_maker'),
            array('admin_maker' => $formData)
        );

        // Check redirect
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_maker')));

        /**
         * @var Crawler $crawler
         */
        $crawler = $this->client->followRedirect();
        // Check message
        $this->assertContains('メーカーを保存しました。', $crawler->filter('.alert')->html());

        // check item name
        $addItem = $crawler->filter('.tableish .item_box')->first()->text();
        $this->assertContains($formData['name'], $addItem);
    }

    /**
     * Test maker edit.
     */
    public function testMakerEditNameIsEmpty()
    {
        $Maker = $this->createMaker(1);
        $formData = $this->createFormData($Maker->getId());
        $formData['name'] = '';

        /**
         * @var Crawler $crawler
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_maker', array('id' => $Maker->getId())),
            array('admin_maker' => $formData)
        );

        // Check message
        $this->assertContains('※ メーカー名が入力されていません。', $crawler->filter('.modal-dialog .attention')->html());
    }

    /**
     * Test maker edit.
     */
    public function testMakerEditNameIsDuplicate()
    {
        $MakerBefore = $this->createMaker(1);
        $Maker = $this->createMaker(1);
        $formData = $this->createFormData($Maker->getId());

        $formData['name'] = $MakerBefore->getName();

        /**
         * @var Crawler $crawler
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_maker', array('id' => $Maker->getId())),
            array('admin_maker' => $formData)
        );

        // Check message
        $this->assertContains('※ 既に使用されています。', $crawler->filter('.modal-dialog .attention')->html());
    }

    /**
     * Test maker edit.
     */
    public function testMakerEditIdIsNotFound()
    {
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $Maker = $this->createMaker(1);
        $editId = $Maker->getId() + 1;
        $formData = $this->createFormData($editId);

        $this->client->request(
            'POST',
            $this->app->url('admin_maker', array('id' => $editId)),
            array('admin_maker' => $formData)
        );
    }

    /**
     * Test maker edit.
     */
    public function testMakerEdit()
    {
        $Maker = $this->createMaker(1);
        $formData = $this->createFormData($Maker->getId());

        $this->client->request(
            'POST',
            $this->app->url('admin_maker', array('id' => $Maker->getId())),
            array('admin_maker' => $formData)
        );

        // Check redirect
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_maker')));

        $crawler = $this->client->followRedirect();
        // Check message
        $this->assertContains('メーカーを保存しました。', $crawler->filter('.alert')->html());

        // Check item name
        $html = $crawler->filter('.tableish .item_box')->first()->text();
        $this->assertContains($formData['name'], $html);
    }

    /**
     * Test maker delete.
     */
    public function testMakerDeleteGetMethod()
    {
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException');
        $Maker = $this->createMaker();

        $this->client->request(
            'GET',
            $this->app->url('admin_maker_delete', array('id' => $Maker->getId()))
        );
        $this->fail('No route found for "GET /admin/product/maker/{id}/delete": Method Not Allowed (Allow: DELETE)');
    }

    /**
     * Test maker delete.
     */
    public function testMakerDeleteIdIsNull()
    {
        $this->client->request(
            'DELETE',
            $this->app->url('admin_maker_delete', array('id' => null))
        );

        // Check redirect
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_maker')));

        $crawler = $this->client->followRedirect();
        // Check message
        $this->assertContains('メーカーが見つかりません。', $crawler->filter('.alert')->html());
    }

    /**
     * Test maker delete.
     */
    public function testMakerDeleteIdIsNotExist()
    {
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();
        $id = $faker->randomNumber(3);

        $this->client->request(
            'DELETE',
            $this->app->url('admin_maker_delete', array('id' => $id))
        );

        $this->fail('Maker not found!');
    }

    /**
     * Test maker edit.
     */
    public function testMakerDelete()
    {
        $Maker = $this->createMaker();

        $this->client->request(
            'DELETE',
            $this->app->url('admin_maker_delete', array('id' => $Maker->getId()))
        );
        // Check redirect
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_maker')));

        $crawler = $this->client->followRedirect();

        // Check message
        $this->assertContains('メーカーを削除しました。', $crawler->filter('.alert')->html());

        // Check item name
        $html = $crawler->filter('.box')->html();
        $this->assertNotContains($Maker->getName(), $html);
    }

    /**
     * Test rank move
     */
    public function testMoveRankTestIsNotPostAjax()
    {
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException');
        $Maker01 = $this->createMaker(1);
        $oldRank = $Maker01->getRank();
        $Maker02 = $this->createMaker(2);
        $newRank = $Maker02->getRank();

        $request = array(
            $Maker01->getId() => $newRank,
            $Maker02->getId() => $oldRank,
        );

        $this->client->request(
            'GET',
            $this->app->url('admin_product_maker_rank_move'),
            $request,
            array(),
            array(
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'CONTENT_TYPE' => 'application/json',
            )
        );

        $this->actual = $Maker01->getRank();
        $this->expected = $oldRank;
        $this->verify();
        $this->fail('No route found for "GET /admin/product/maker/rank/move": Method Not Allowed (Allow: POST)');
    }

    /**
     * Move rank test
     */
    public function testMoveRank()
    {
        $Maker01 = $this->createMaker(1);
        $oldRank = $Maker01->getRank();
        $Maker02 = $this->createMaker(2);
        $newRank = $Maker02->getRank();

        $request = array(
            $Maker01->getId() => $newRank,
            $Maker02->getId() => $oldRank,
        );

        $this->client->request(
            'POST',
            $this->app->url('admin_product_maker_rank_move'),
            $request,
            array(),
            array(
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'CONTENT_TYPE' => 'application/json',
            )
        );

        $this->actual = $Maker01->getRank();
        $this->expected = $newRank;
        $this->verify();
    }

    /**
     * Create data form.
     *
     * @param null $makerId
     *
     * @return array
     */
    private function createFormData($makerId = null)
    {
        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();

        $form = array(
            '_token' => 'dummy',
            'name' => $faker->word,
            'id' => $makerId,
        );

        return $form;
    }
}