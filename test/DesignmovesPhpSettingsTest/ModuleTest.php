<?php
/**
 * Copyright (c) 2014 - 2015, Designmoves (http://www.designmoves.nl)
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * * Neither the name of Designmoves nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace DesignmovesPhpSettingsTest;

use DesignmovesPhpSettings\Module;
use PHPUnit_Framework_TestCase;
use Zend\EventManager\EventManager;
use Zend\ModuleManager\Listener\ConfigListener;
use Zend\ModuleManager\ModuleEvent;
use Zend\ModuleManager\ModuleManager;

/**
 * @coversDefaultClass DesignmovesPhpSettings\Module
 */
class ModuleTest extends PHPUnit_Framework_TestCase
{
    /*
     * @var Module
     */
    protected $module;

    public function setUp()
    {
        $this->module = new Module;
    }

    /**
     * @covers ::init
     */
    public function testInitAttachesLoadModulesPostEvent()
    {
        $modules       = array('DesignmovesPhpSettings');
        $eventManager  = new EventManager;
        $moduleManager = new ModuleManager($modules, $eventManager);

        $this->module->init($moduleManager);

        $eventName = ModuleEvent::EVENT_LOAD_MODULES_POST;
        $queue     = $eventManager->getListeners($eventName);
        $this->assertCount(1, $queue);

        $callbackHandlers = $queue->toArray();
        $this->assertCount(1, $callbackHandlers);

        $callbackHandler = $callbackHandlers[0];

        // Event name
        $this->assertSame($eventName, $callbackHandler->getMetaDatum('event'));

        // Callback
        $callback = $callbackHandler->getCallback();
        $this->assertInstanceOf('DesignmovesPhpSettings\Module', $callback[0]);
        $this->assertSame('onModulesLoaded', $callback[1]);

        // Priority
        $this->assertSame(10000, $callbackHandler->getMetaDatum('priority'));
    }

    /**
     * @covers ::onModulesLoaded
     */
    public function testOnModulesLoadedCanSetPhpSettings()
    {
        $mergedConfig = array(
            'designmoves_php_settings' => array(
                'date.timezone' => 'Pacific/Fiji',
            ),
        );

        $configListener = new ConfigListener;
        $configListener->setMergedConfig($mergedConfig);

        $moduleEvent = new ModuleEvent;
        $moduleEvent->setConfigListener($configListener);

        $this->module->onModulesLoaded($moduleEvent);

        $config = $mergedConfig['designmoves_php_settings'];
        foreach ($config as $name => $value) {
            $errorMessage = sprintf(
                'Value of %s is expected to be "%s", "%s" given',
                $name,
                $value,
                ini_get($name)
            );
            $this->assertSame($value, ini_get($name), $errorMessage);
        }
    }
}
