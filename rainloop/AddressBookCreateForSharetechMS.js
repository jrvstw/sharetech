import ko from 'ko';
import _ from '_';
import $ from '$';

import { StorageResultType, Notification, Magics } from 'Common/Enums';
import { UNUSED_OPTION_VALUE } from 'Common/Consts';
import { bMobileDevice } from 'Common/Globals';
import { trim, defautOptionsAfterRender, adddressBookListOptionsBuilder, pInt } from 'Common/Utils';

import { Selector } from 'Common/Selector';
import ContactStore from 'Stores/User/Contact';
import { ContactModel } from 'Model/Contact';

import Promises from 'Promises/User/Ajax';
import Remote from 'Remote/User/Ajax';
import { getApp } from 'Helper/Apps/User';

import { popup, command } from 'Knoin/Knoin';
import { AbstractViewNext } from 'Knoin/AbstractViewNext';

@popup({
	name: 'View/Popup/AddressBook',
	templateID: 'PopupsAddressBookCreate'
})
class AddressBookCreateView extends AbstractViewNext {
	constructor() {
		super();

		this.contacts = ko.observableArray([]);
		this.datas = [];
		this.selectedData = ko.observableArray([]);
		this.currentContact = ko.observable(null);
		this.shared = ko.observable(false);
		this.addressbook = ko.observable(0);
		this.checkedCount = ko.observable(0);
		this.originalName = '';
		this.folderName = ko.observable('');
		this.folderName.focused = ko.observable(false);
		this.loading = ko.observable(false);
		this.listSort = {
			'default': 'POPUPS_CREATE_FOLDER/SORT_ALL',
			'seleted': 'POPUPS_CREATE_FOLDER/SORT_SELETED',
			'unseleted': 'POPUPS_CREATE_FOLDER/SORT_UNSELETED'
		};
		this.scrollbarInstance = '';

		this.search = ko.observable('');
		this.search.subscribe((item) => {
			this.loading(true);
			this.contacts([]);
			this.scrollbarInstance.scroll('0%');
			this.start = 0;

			const sort = this.sort();

			this.selectedData(
				this.datas.filter(
					(o) =>
						('default' === sort || o.checked() === ('seleted' === sort)) &&
						(-1 !== o.username.indexOf(item) || -1 !== o.name.indexOf(item))
				)
			);

			this.reloadList();

			_.delay(() => {
				this.loading(false);
			}, Magics.Time100ms);
		});

		this.sort = ko.observable('default');
		this.sort.subscribe((item) => {
			this.loading(true);
			this.contacts([]);
			this.scrollbarInstance.scroll('0%');
			this.start = 0;

			const checked = 'seleted' === item,
				search = this.search();

			this.selectedData(
				this.datas.filter(
					(o) =>
						('default' === item || o.checked() === checked) &&
						(0 === search.length || -1 !== o.username.indexOf(search) || -1 !== o.name.indexOf(search))
				)
			);

			this.reloadList();

			_.delay(() => {
				this.loading(false);
			}, Magics.Time100ms);
		});

		this.start = 0;
		this.offset = 15;

		this.selectedParentValue = ko.observable(UNUSED_OPTION_VALUE);

		this.parentFolderSelectList = ko.computed(() => {
			const top = [],
				list = ContactStore.addressBooks();

			return adddressBookListOptionsBuilder(list, this.shared(), [-2], top, null, null);
		});

		this.contacts.subscribe((items) => {
			this.start = items.length;
		});

		this.defautOptionsAfterRender = defautOptionsAfterRender;

		this.selector = new Selector(
			this.contacts,
			this.currentContact,
			null,
			'.e-item .actionHandle',
			'.e-item.selected',
			'.e-item .checkboxItem',
			'.e-item.focused'
		);

		this.selector.on('onItemGetUid', (contact) => (contact ? contact.generateUid() : ''));

		this.contactListChecked = ko
			.computed(() => _.filter(this.selectedData(), (item) => item.checked()))
			.extend({ rateLimit: 0 });

		this.isIncompleteChecked = ko.computed(() => {
			const m = this.selectedData().length,
				c = this.contactListChecked().length;
			return 0 < m && 0 < c && m > c;
		});

		this.checkAll = ko.computed({
			read: () => 0 < this.contactListChecked().length,
			write: (value) => {
				value = !!value;
				_.each(this.selectedData(), (contact) => {
					contact.checked(value);
				});
			}
		});
	}

	@command((self) => self.simpleFolderNameValidation(self.folderName()))
	createFolderCommand() {
		const creates = [],
			deletes = [];

		_.each(this.contacts(), (data) => {
			if (data.checked() !== data.original) {
				if (false === data.original) {
					creates.push(data.generateUid());
				} else {
					deletes.push(data.generateUid());
				}
			}
		});

		const parentAddressBookId = this.selectedParentValue();

		if (this.folderName() !== this.originalName || 0 < creates.length || 0 < deletes.length) {
			getApp().addressBookPromisesActionHelper(
				Promises.msAddressBookCreate(
					this.folderName(),
					this.originalName,
					this.addressbook(),
					parentAddressBookId,
					creates,
					deletes,
					this.shared(),
					ContactStore.foldersCreating
				),
				Notification.CantCreateFolder
			);
		}

		this.cancelCommand();
	}

	simpleFolderNameValidation(sName) {
		return /^[^\\/]+$/g.test(trim(sName));
	}

	clearPopup() {
		this.folderName('');
		this.selectedParentValue('');
		this.folderName.focused(false);
	}

	reloadList() {
		this.contacts(this.selectedData().slice(this.start, 100));
	}

	onShow(folder, shared, uid = 0) {
		this.clearPopup();
		this.shared(shared);
		this.addressbook(uid);
		this.folderName(folder);
		this.originalName = folder;
		this.loading(true);

		Remote.msContactsList(
			(sResult, oData) => {
				if (StorageResultType.Success === sResult && oData && oData.Result) {
					this.datas = _.map(oData.Result, (item) => {
						const contact = new ContactModel();

						contact.display = item.email + '(' + item.display + ')';
						contact.checked('1' === item.checked);
						contact.idContact = pInt(item.id);
						contact.username = item.email;
						contact.name = item.display;
						contact.original = '1' === item.checked;
						contact.visible = ko.observable(false);

						return contact;
					});
					this.selectedData(this.datas);
					this.contacts(this.selectedData().slice(this.start, 200));
				}

				this.loading(false);
			},
			this.shared(),
			this.addressbook()
		);
	}

	onHide() {
		this.folderName('');
		this.checkedCount(0);
		this.folderName.focused(false);
		this.contacts([]);
		this.start = 0;
		this.datas = [];
		this.selectedData([]);
		this.search('');
		this.sort('default');
	}

	onBuild(dom) {
		this.oContentVisible = $('.b-contacts-create', dom);
		this.oContentScrollable = $('.contact', this.oContentVisible);
		this.selector.init(this.oContentVisible, this.oContentScrollable);

		this.oContentScrollable.on('ReachBottom', () => {
			const contact = this.contacts();
			this.contacts(contact.concat(this.selectedData().slice(this.start, this.start + this.offset)));
		});

		if ('' === this.scrollbarInstance) {
			this.scrollbarInstance = $('.b-contact-list.contact', dom).overlayScrollbars();
		}

		dom.on('click', '.b-list-toolbar .checkboxCkeckAll', () => {
			this.checkAll(!this.checkAll());
		});
	}

	onShowWithDelay() {
		if (!bMobileDevice) {
			this.folderName.focused(true);
		}
	}
}

export { AddressBookCreateView, AddressBookCreateView as default };
