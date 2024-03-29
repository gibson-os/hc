Ext.define('GibsonOS.module.hc.neopixel.animation.View', {
    extend: 'GibsonOS.module.core.component.view.View',
    alias: ['widget.gosModuleHcNeopixelAnimationView'],
    itemSelector: 'div.hcNeopixelAnimationViewElement',
    selectedItemCls: 'hcNeopixelAnimationViewElementSelected',
    style: 'background: #FFF;',
    pixelPerSecond: 100,
    initComponent: function() {
        let me = this;

        me.store = new GibsonOS.module.hc.neopixel.store.Animation();
        me.tpl = new Ext.XTemplate(
            '<div class="hcNeopixelAnimationViewHeader">',
                '<div class="hcNeopixelAnimationViewHeaderTime">Zeit</div>',
                '<div class="hcNeopixelAnimationViewHeaderTimeline">',
                    '<div class="hcNeopixelAnimationViewHeaderTimelineContainer">',
                    '</div>',
                '</div>',
            '</div>',
            '<div class="hcNeopixelAnimationViewLeds">',
                '<div class="hcNeopixelAnimationViewLedsContainer">',
                    '{[this.renderLeds()]}',
                '</div>',
            '</div>',
            '<div class="hcNeopixelAnimationViewElements">',
                '<div class="hcNeopixelAnimationViewElementsContainer">',
                    '<tpl for=".">',
                        '{[this.renderElement(values)]}',
                    '</tpl>',
                '</div>',
            '</div>',
            {
                renderLeds: function() {
                    let leds = '';

                    if (!me.leds) {
                        return leds;
                    }

                    let i = 1;

                    Ext.iterate(me.leds, (ledId) => {
                        leds += '<div data-id="' + ledId + '">Pixel ' + (i++) + '</div>';
                    })

                    return leds;
                },
                renderElement: function(element) {
                    let div = '<div class="hcNeopixelAnimationViewElement" style="';

                    if (element.deactivated) {
                        div += 'background-color: transparent; border: 1px solid #000;';
                    } else {
                        if (element.fadeIn) {
                            const store = me.getStore();
                            let ledIndex = store.find('ledId', element.ledId, 0, false, false, true);
                            let lastLed = null;

                            while (ledIndex > -1) {
                                const ledRecord = store.getAt(ledIndex);

                                if (
                                    ledRecord.get('time') < element.time &&
                                    (
                                        !lastLed ||
                                        ledRecord.get('time') > lastLed.get('time')
                                    )
                                ) {
                                    lastLed = ledRecord;
                                }

                                ledIndex = store.find('ledId', element.ledId, ledIndex+1, false, false, true);
                            }

                            let lastLedRed = 0;
                            let lastLedGreen = 0;
                            let lastLedBlue = 0;

                            if (lastLed) {
                                lastLedRed = lastLed.get('red');
                                lastLedGreen = lastLed.get('green');
                                lastLedBlue = lastLed.get('blue');
                            }

                            let gradientWidth = me.up().down('gosModuleHcNeopixelColorFadeIn').findRecordByValue(element.fadeIn).get('seconds') * me.pixelPerSecond;

                            div +=
                                'background: linear-gradient(' +
                                    'to right, ' +
                                    'rgb(' + lastLedRed + ', ' + lastLedGreen + ', ' + lastLedBlue + '), ' +
                                    'rgb(' + element.red + ', ' + element.green + ', ' + element.blue + ') ' + gradientWidth + 'px' +
                                '); '
                            ;
                        }

                        div += 'background-color: rgb(' + element.red + ', ' + element.green + ', ' + element.blue + '); ';
                    }

                    div += 'width: ' + ((element.length / 1000) * me.pixelPerSecond) + 'px; ';
                    div += 'top: ' + ((me.leds[element.ledId].number * 23) + 4) + 'px; ';
                    div += 'left: ' + ((element.time / 1000) * me.pixelPerSecond + 1) + 'px;';

                    return div + '">&nbsp;</div>';
                }
            }
        );
        me.lastSelectedLed = null;

        me.callParent();

        me.on('refresh', function() {
            let ledsContainer = document.querySelector('#' + me.getId() + ' .hcNeopixelAnimationViewLedsContainer');

            Ext.iterate(me.selectedLeds, function(ledId) {
                let ledDiv = ledsContainer.querySelector('[data-id="' + ledId + '"]');
                ledDiv.classList.add('selected');
            });

            me.setDimensions();
            me.setLedClickEvents();
        });
        me.getStore().on('add', function() {
            me.setDimensions();
        });
    },
    updateTemplate(leds) {
        let me = this;

        me.fireEvent('updateTemplate', [me, leds]);
        me.leds = {};

        Ext.iterate(leds, (led) => {
            me.leds[led.id] = led;
        });

        me.refresh();
    },
    setDimensions() {
        let me = this;
        let elementsDiv = document.querySelector('#' + me.getId() + ' .hcNeopixelAnimationViewElements');
        let elementsContainer = document.querySelector('#' + me.getId() + ' .hcNeopixelAnimationViewElementsContainer');
        let ledsContainer = document.querySelector('#' + me.getId() + ' .hcNeopixelAnimationViewLedsContainer');
        let timeline = document.querySelector('#' + me.getId() + ' .hcNeopixelAnimationViewHeaderTimeline');
        let timelineContainer = document.querySelector('#' + me.getId() + ' .hcNeopixelAnimationViewHeaderTimelineContainer');

        let milliseconds = 10000;

        me.getStore().each(function(record) {
            if (milliseconds > record.get('time') + record.get('length')) {
                return true;
            }

            milliseconds = record.get('time') + record.get('length') + 1000;
        });

        let seconds = Math.ceil(milliseconds / 1000);
        elementsContainer.style.height = ledsContainer.offsetHeight + 'px';
        elementsContainer.style.width = (seconds * me.pixelPerSecond) + 'px';
        timeline.style.width = (seconds * me.pixelPerSecond) + 'px';
        timelineContainer.innerHTML = '';

        for (let i = 0; i < seconds; i++) {
            timelineContainer.innerHTML += '<div>' + transformSeconds(i) + '</div>';
        }

        elementsDiv.onscroll = function() {
            ledsContainer.style.marginTop = 0 - elementsDiv.scrollTop;
            timelineContainer.style.marginLeft = 0 - elementsDiv.scrollLeft;
        };
    },
    setLedClickEvents() {
        let me = this;
        let ledsContainer = document.querySelector('#' + me.getId() + ' .hcNeopixelAnimationViewLedsContainer');

        Ext.iterate(ledsContainer.querySelectorAll('div'), function(ledDiv) {
            ledDiv.onclick =  function(event) {
                me.fireEvent('ledSelectionChange', me, ledDiv, event);

                if (!event.ctrlKey) {
                    Ext.iterate(ledsContainer.querySelectorAll('div.selected'), function(selectedLedDiv) {
                        selectedLedDiv.classList.remove('selected');
                    });
                }

                if (event.shiftKey) {
                    //die differenz zwischen lastSelectedLed und ledDiv selecten
                    let select = false;

                    Ext.iterate(ledsContainer.childNodes, function(ledNode) {
                        if (select) {
                            ledNode.classList.add('selected');
                        }

                        if (
                            ledNode === me.lastSelectedLed ||
                            ledNode === ledDiv
                        ) {
                            select = !select;
                        }

                        if (select) {
                            ledNode.classList.add('selected');
                        }
                    });
                } else {
                    me.lastSelectedLed = ledDiv;

                    if (ledDiv.classList.contains('selected')) {
                        ledDiv.classList.remove('selected');
                    } else {
                        ledDiv.classList.add('selected');
                    }
                }

                me.selectedLeds = [];

                Ext.iterate(ledsContainer.querySelectorAll('div.selected'), function(selectedLedDiv) {
                    me.selectedLeds.push(selectedLedDiv.dataset.id);
                });

                me.fireEvent('afterLedSelectionChange', me, ledDiv, event);
            };
        });
    }
});