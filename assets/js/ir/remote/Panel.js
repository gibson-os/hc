Ext.define('GibsonOS.module.hc.ir.remote.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcIrRemotePanel'],
    layout: 'border',
    enableContextMenu: true,
    enableKeyEvents: true,
    remoteId: null,
    addFunction() {
        const store = this.viewItem.getStore();
        let maxTop = 0;

        store.each((key) => {
            if (key.get('left') <= 3) {
                let top = key.get('top');
                let height = key.get('height');
                maxTop = (maxTop > top + height) ? maxTop : (top + height + 1);
            }
        });

        store.add(new GibsonOS.module.hc.ir.model.remote.Button({
            left: 0,
            top: maxTop,
            width: 3,
            height: 3
        }));
    },
    deleteFunction(records) {
        this.viewItem.getStore().remove(records);
    },
    initComponent() {
        const me = this;

        me.viewItem = new GibsonOS.module.hc.ir.remote.View({
            region: 'center',
            moduleId: me.moduleId,
            remoteId: me.remoteId,
            overflowX: 'auto',
            overflowY: 'auto'
        });

        me.items = [me.viewItem, {
            xtype: 'gosModuleHcIrRemoteForm',
            region: 'east',
            disabled: true,
            flex: 0,
            width: 300
        }];

        me.callParent();

        me.addActions();

        me.down('gosModuleHcIrRemoteView').getStore().on('load', (store) => {
            me.down('#name').setValue(store.getProxy().getReader().rawData.data.name);
        });

        me.viewItem.on('selectionchange', (view, records) => {
            const form = me.down('form');
            const keyStore = me.down('gosModuleIrRemoteKeyGrid').getStore();

            keyStore.removeAll();

            if (records.length !== 1) {
                form.disable();
                form.loadRecord(new GibsonOS.module.hc.ir.model.remote.Button());

                return;
            }

            form.loadRecord(records[0]);

            Ext.iterate(records[0].get('keys'), (key) => {
                keyStore.add(key.key);
            });

            form.enable();
        });

        me.down('form').getForm().getFields().each((field) => {
            field.on('change', (field, value) => {
                const keys = me.viewItem.getSelectionModel().getSelection();

                if (keys.length !== 1) {
                    return;
                }

                keys[0].set(field.name, value);
            });
        });
        const changeKeyGridFunction = (store) => {
            const key = me.viewItem.getSelectionModel().getSelection()[0];
            let setKeys = [];
            let order = 0;
            store.each((setKey) => setKeys.push({
                key: setKey.getData(),
                order: order++
            }));

            key.set('keys', setKeys);
        };
        const keyStore = me.down('gosModuleIrRemoteKeyGrid').getStore();
        keyStore.on('add', changeKeyGridFunction);
        keyStore.on('remove', changeKeyGridFunction);

        me.viewItem.on('render', function() {
            me.viewItem.dragZone = Ext.create('Ext.dd.DragZone', me.viewItem.getEl(), {
                getDragData: function(event) {
                    let sourceElement = event.getTarget(me.viewItem.itemSelector, 10);

                    if (sourceElement) {
                        let clone = sourceElement.cloneNode(true);
                        clone.style = 'position: relative;';

                        return me.viewItem.dragData = {
                            sourceEl: sourceElement,
                            repairXY: Ext.fly(sourceElement).getXY(),
                            ddel: clone,
                            record: me.viewItem.getRecord(sourceElement)
                        };
                    }
                },
                getRepairXY: function() {
                    return me.viewItem.dragData.repairXY;
                }
            });
            me.viewItem.dropZone = GibsonOS.dropZones.add(me.viewItem.getEl(), {
                getTargetFromEvent: function(event) {
                    return event.getTarget('#' + me.viewItem.getId());
                },
                onNodeOver: function(target, dd, event, data) {
                    if (data.record instanceof GibsonOS.module.hc.ir.model.remote.Button) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }

                    return Ext.dd.DropZone.prototype.dropNotAllowed;
                },
                onNodeDrop: function(target, dd, event, data) {
                    let element = me.viewItem.getEl().dom;
                    let boundingClientRect = element.getBoundingClientRect();
                    let elementLeft = (dd.lastPageX + element.scrollLeft) - boundingClientRect.x;
                    let elementTop = (dd.lastPageY + element.scrollTop) - boundingClientRect.y;

                    data.record.set('left', Math.floor((elementLeft - me.viewItem.offsetLeft - 17) / me.viewItem.gridSize));
                    data.record.set('top', Math.floor((elementTop - me.viewItem.offsetTop - 25) / me.viewItem.gridSize));

                    me.viewItem.getStore().each((key) => key.commit());
                }
            });
        });
        me.viewItem.on('itemkeydown', function(view, record, item, index, event) {
            let moveRecords = function(left, top) {
                Ext.iterate(me.viewItem.getSelectionModel().getSelection(), function(record) {
                    record.set('left', record.get('left') + left);
                    record.set('top', record.get('top') + top);
                });

                me.viewItem.getStore().each((key) => key.commit());
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
    addActions() {
        const me = this;

        me.addAction({xtype: 'tbseparator'});
        me.addAction({
            xtype: 'gosCoreComponentFormFieldTextField',
            addToContainerContextMenu: false,
            addToItemContextMenu: false,
            itemId: 'name',
            emptyText: 'Name',
            hideLabel: true,
            width: 120
        });
        me.addAction({
            iconCls: 'icon_system system_save',
            addToContainerContextMenu: false,
            addToItemContextMenu: false,
            requiredPermission: {
                action: 'remote',
                method: 'POST',
                permission: GibsonOS.Permission.WRITE + GibsonOS.Permission.MANAGE
            },
            handler: function() {
                me.setLoading(true);
                let keys = [];

                me.viewItem.store.each((key) => {
                    keys.push(key.getData());
                });

                GibsonOS.Ajax.request({
                    url: baseDir + 'hc/ir/remote',
                    method: 'POST',
                    params: {
                        moduleId: me.moduleId,
                        id: me.remoteId ?? 0,
                        name: me.down('#name').getValue(),
                        buttons: Ext.encode(keys)
                    },
                    callback() {
                        me.setLoading(false);
                    },
                    success() {
                        //me.up('window').close();
                    }
                });
            }
        });
    }
});