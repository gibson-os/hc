Ext.define('GibsonOS.module.hc.ir.remote.View', {
    extend: 'GibsonOS.module.core.component.view.View',
    alias: ['widget.gosModuleHcIrRemoteView'],
    multiSelect: false,
    singleSelect: true,
    trackOver: true,
    itemSelector: 'div.hcIrRemoteKey',
    selectedItemCls: 'hcIrRemoteKeySelected',
    overItemCls: 'hcIrRemoteKeyHover',
    remote: {
        name: null,
        itemWidth: 30,
        width: 0,
        height: 0
    },
    gridSize: 10,
    offsetTop: 6,
    offsetLeft: 6,
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.ir.store.RemoteKey({
            remoteId: me.remote.id
        });
        let id = Ext.id();
        me.tpl = new Ext.XTemplate(
            '<tpl for=".">',
                '<div ',
                    'id="' + id + '{number}" ',
                    'class="hcIrRemoteKey" ',
                    'style="',
                        'left: {left*' + me.gridSize + '+' + me.offsetLeft + '}px; ',
                        'top: {top*' + me.gridSize + '+' + me.offsetTop + '}px; ',
                        'width: {width*' + me.gridSize + '}px; ',
                        'height: {height*' + me.gridSize + '}px; ',
                        '">',
                    '{name}',
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
                    if (data.record instanceof GibsonOS.module.hc.ir.model.RemoteKey) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }

                    return Ext.dd.DropZone.prototype.dropNotAllowed;
                },
                onNodeDrop: function(target, dd, event, data) {
                    let element = me.getEl().dom;
                    let boundingClientRect = element.getBoundingClientRect();
                    let elementLeft = (dd.lastPageX + element.scrollLeft) - boundingClientRect.x;
                    let elementTop = (dd.lastPageY + element.scrollTop) - boundingClientRect.y;

                    data.record.set('left', Math.floor((elementLeft - me.offsetLeft - 17) / me.gridSize));
                    data.record.set('top', Math.floor((elementTop - me.offsetTop - 25) / me.gridSize));

                    me.getStore().each((key) => key.commit());
                }
            });
        });
        me.on('itemkeydown', function(view, record, item, index, event) {
            let moveRecords = function(left, top) {
                Ext.iterate(me.getSelectionModel().getSelection(), function(record) {
                    record.set('left', record.get('left') + left);
                    record.set('top', record.get('top') + top);
                });

                me.getStore().each((key) => key.commit());
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
    },
});