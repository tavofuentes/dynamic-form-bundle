<?php

namespace Linio\DynamicFormBundle\Tests\FormlyMapper;

use Linio\DynamicFormBundle\FormlyMapper\FormlyMapper;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Security\Csrf\CsrfToken;

class FormlyMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CsrfToken
     */
    protected $csrfToken;

    /**
     * @var ObjectProphecy
     */
    protected $csrfTokenManagerMock;

    /**
     * @var ObjectProphecy
     */
    protected $formFactoryMock;

    /**
     * @var ObjectProphecy
     */
    protected $formlyFieldMock;

    /**
     * @var ObjectProphecy
     */
    protected $formlyFieldFactoryMock;

    /**
     * @var FormlyMapper
     */
    protected $formlyMapper;

    public function testIsMappingCorrectly()
    {
        $formName = 'foo';
        $formType = 'number';

        $configuration = [
            'width' => [
                'type' => 'number',
                'options' => [
                    'required' => true,
                    'label' => 'Width',
                ],
            ],
        ];

        $this->formFactoryMock->getConfiguration($formName)
            ->willReturn($configuration);

        $this->formlyFieldFactoryMock->getFormlyField($formType)
            ->willReturn($this->formlyFieldMock->reveal());

        $fieldConfiguration = [
            'name' => 'width',
            'type' => 'number',
            'options' => [
                'required' => true,
                'label' => 'Width',
            ],
        ];

        $this->formlyFieldMock->setFieldConfiguration($fieldConfiguration)
            ->shouldBeCalled();

        $formlyConfiguration = [
            [
                'key' => 'width',
                'type' => 'input',
                'templateOptions' => [
                    'type' => 'number',
                    'label' => 'Width',
                    'required' => true,
                ],
            ],
        ];

        $this->formlyFieldMock->getFormlyFieldConfiguration()
            ->willReturn($formlyConfiguration);

        $this->csrfTokenManagerMock->refreshToken($formName)
            ->willReturn($this->csrfToken);

        $this->formlyMapper->setFormFactory($this->formFactoryMock->reveal());
        $this->formlyMapper->setFormlyFieldFactory($this->formlyFieldFactoryMock->reveal());
        $this->formlyMapper->setCsrfTokenManager($this->csrfTokenManagerMock->reveal());

        $expected = [
            $formlyConfiguration,
            [
                'key' => '_token',
                'type' => 'hidden',
                'defaultValue' => 'bar',
            ],
        ];

        $actual = $this->formlyMapper->map($formName);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \Linio\DynamicFormBundle\Exception\FormlyMapperException
     */
    public function testIsThrowingNonExistentFormException()
    {
        $formName = 'foo';

        $this->formFactoryMock->getConfiguration($formName)
            ->willThrow('Linio\DynamicFormBundle\Exception\NonExistentFormException');

        $this->formlyMapper->setFormFactory($this->formFactoryMock->reveal());

        $this->formlyMapper->map($formName);
    }

    public function setup()
    {
        $this->csrfToken = new CsrfToken('foo', 'bar');
        $this->csrfTokenManagerMock = $this->prophesize('Symfony\Component\Security\Csrf\CsrfTokenManagerInterface');
        $this->formFactoryMock = $this->prophesize('Linio\DynamicFormBundle\Form\FormFactory');
        $this->formlyFieldFactoryMock = $this->prophesize('Linio\DynamicFormBundle\FormlyMapper\FormlyField\FormlyFieldFactory');
        $this->formlyFieldMock = $this->prophesize('Linio\DynamicFormBundle\FormlyMapper\FormlyField');

        $this->formlyMapper = new FormlyMapper();
    }
}
