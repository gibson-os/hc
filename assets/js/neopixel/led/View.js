Ext.define('GibsonOS.module.hc.neopixel.led.View', {
    extend: 'GibsonOS.core.component.view.View',
    alias: ['widget.gosModuleHcNeopixelLedView'],
    itemSelector: 'div.hcNeopixelLed',
    selectedItemCls: 'hcNeopixelLedSelected',
    multiSelect: true,
    overflowX: 'auto',
    overflowY: 'auto',
    ledSize: 12,
    ledOffsetTop: 6,
    ledOffsetLeft: 6,
    initComponent: function () {
        let me = this;
        let id = Ext.id();

        me.store = new GibsonOS.module.hc.neopixel.store.View();
        me.tpl = new Ext.XTemplate(
            '<tpl for=".">',
                '<div ',
                    'id="' + id + '{number}" ',
                    'class="hcNeopixelLed" ',
                    'style="',
                        'left: {left*' + me.ledSize + '+' + me.ledOffsetLeft + '}px; ',
                        'top: {top*' + me.ledSize + '+' + me.ledOffsetTop + '}px; ',
                        'background-color: rgb({red}, {green}, {blue});">',
                    '{number+1}',
                '</div>',
            '</tpl>'
        );

        me.callParent();

        me.on('render', function() {
            me.dragZone = Ext.create('Ext.dd.DragZone', me.getEl(), {
                getDragData: function(event) {
                    let sourceElement = event.getTarget(me.itemSelector, 10);

                    if (sourceElement) {
                        let clone = sourceElement.cloneNode(true);
                        clone.style = 'position: relative;';

                        return me.dragData = {
                            sourceEl: sourceElement,
                            repairXY: Ext.fly(sourceElement).getXY(),
                            ddel: clone,
                            record: me.getRecord(sourceElement)
                        };
                    }
                },
                getRepairXY: function() {
                    return me.dragData.repairXY;
                }
            });
            me.dropZone = GibsonOS.dropZones.add(me.getEl(), {
                getTargetFromEvent: function(event) {
                    return event.getTarget('#' + me.getId());
                },
                onNodeOver: function(target, dd, event, data) {
                    if (data.record instanceof GibsonOS.module.hc.neopixel.model.Led) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }

                    return Ext.dd.DropZone.prototype.dropNotAllowed;
                },
                onNodeDrop: function(target, dd, event, data) {
                    let element = me.getEl().dom;
                    let boundingClientRect = element.getBoundingClientRect();
                    let elementLeft = (dd.lastPageX + element.scrollLeft) - boundingClientRect.x;
                    let elementTop = (dd.lastPageY + element.scrollTop) - boundingClientRect.y;
                    let oldLeft = data.record.get('left');
                    let oldTop = data.record.get('top');

                    data.record.set('left', Math.floor((elementLeft - me.ledOffsetLeft - 17) / me.ledSize));
                    data.record.set('top', Math.floor((elementTop - me.ledOffsetTop - 25) / me.ledSize));

                    let leds = {};

                    me.getStore().each(function(led) {
                        leds[led.getId()] = led.getData();
                        led.commit();
                    });

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/neopixel/saveLeds',
                        params: {
                            moduleId: me.gos.data.module.id,
                            leds: Ext.encode(leds)
                        },
                        failure: function() {
                            data.record.set('left', oldLeft);
                            data.record.set('top', oldTop);
                        }
                    });
                }
            });
        });
        let moveCount = 0;
        me.on('itemkeydown', function(view, record, item, index, event) {
            let moveRecords = function(left, top) {
                let oldLefts = {};
                let oldTops = {};

                Ext.iterate(me.getSelectionModel().getSelection(), function(record) {
                    oldLefts[record.getId()] = record.get('left');
                    oldTops[record.getId()] = record.get('top');

                    record.set('left', record.get('left') + left);
                    record.set('top', record.get('top') + top);
                });

                let leds = {};

                me.getStore().each(function(led) {
                    leds[led.getId()] = led.getData();
                    led.commit();
                });
                moveCount++;
                setTimeout(function() {
                    moveCount--;

                    if (moveCount !== 0) {
                        return;
                    }

                    GibsonOS.Ajax.request({
                        url: baseDir + 'hc/neopixel/saveLeds',
                        params: {
                            moduleId: me.gos.data.module.id,
                            leds: Ext.encode(leds)
                        },
                        failure: function () {
                            Ext.iterate(me.getSelectionModel().getSelection(), function (record) {
                                record.set('left', oldLefts[record.getId()]);
                                record.set('top', oldTops[record.getId()]);
                            });
                        }
                    });
                }, 500);
            };

            switch (event.getKey()) {
                case Ext.EventObject.S:
                    moveRecords(0, 1);
                    break;
                case Ext.EventObject.W:
                    moveRecords(0, -1);
                    break;
                case Ext.EventObject.A:
                    moveRecords(-1, 0);
                    break;
                case Ext.EventObject.D:
                    moveRecords(1, 0);
                    break;
            }
        });
    }
});