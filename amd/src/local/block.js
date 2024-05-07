import {BaseComponent} from 'core/reactive';
import {get_string as getString} from 'core/str';
import {getCurrentCourseEditor} from "core_courseformat/courseeditor";
import BaseFactory from "../app/factory";

export default class Block extends BaseComponent {
    /**
     * @type {CourseElement}
     */
    course;

    /**
     * @type {BlockElement}
     */
    block;

    /**
     * @type {QueueElement}
     */
    queue;

    /**
     * Constructor hook.
     * @param {Object} descriptor
     */
    create(descriptor) {
        // Optional component name for debugging.
        this.name = 'sharing_cart_block';
        // Default query selectors.
        this.selectors = {};

        this.canBackupUserdata = descriptor.canBackupUserdata ?? false;
        this.canAnonymizeUserdata = descriptor.canAnonymizeUserdata ?? false;
        this.showSharingCartBasket = descriptor.showSharingCartBasket ?? false;
    }

    /**
     * Static method to create a component instance form the mustache template.
     *
     * @param {String} target
     * @param {Boolean} canBackupUserdata
     * @param {Boolean} canAnonymizeUserdata
     * @param {Boolean} showSharingCartBasket
     */
    static init(target, canBackupUserdata, canAnonymizeUserdata, showSharingCartBasket) {
        return new this({
            element: document.getElementById(target),
            reactive: getCurrentCourseEditor(),
            canBackupUserdata,
            canAnonymizeUserdata,
            showSharingCartBasket
        });
    }

    /**
     * Initial state ready method.
     */
    stateReady() {
        this.baseFactory = BaseFactory.make();
        const {course, block, queue} = this.baseFactory.block().eventHandler().onLoad(
            this.canBackupUserdata,
            this.canAnonymizeUserdata,
            this.showSharingCartBasket
        );

        this.course = course;
        this.block = block;
        this.queue = queue;

        const courseContent = document.querySelector('.course-content');
        if (courseContent) {
            const sectionElements = courseContent.querySelectorAll('[data-for="section"]');
            sectionElements.forEach(sectionElement => {
                const section = this.reactive.state.section.get(sectionElement.dataset.id);
                this._refreshSection({element: section});
            });

            const courseModuleElements = courseContent.querySelectorAll('[data-for="cmitem"]');
            courseModuleElements.forEach(courseModuleElement => {
                const courseModule = this.reactive.state.cm.get(courseModuleElement.dataset.id);
                this._refreshCourseModule({element: courseModule});
            });
        }
    }

    /**
     * Component watchers.
     *
     * @returns {Array} of watchers
     */
    getWatchers() {
        return [
            {watch: `section:created`, handler: this._refreshSection},
            {watch: `section:updated`, handler: this._refreshSection},
            {watch: `cm:created`, handler: this._refreshCourseModule},
            {watch: `cm:updated`, handler: this._refreshCourseModule},
        ];
    }

    async getBackupToSharingCartButton() {
        if (!this._sharingCartButton) {
            this._sharingCartButton = await this.baseFactory.moodle().template().createElementFromTemplate(
                'block_sharing_cart/block/course/add_to_sharing_cart_button',
                {}
            );
        }

        return this._sharingCartButton.cloneNode(true);
    }

    /**
     * Refresh the section.
     * @param {Object} param
     * @param {Object} param.element
     */
    async _refreshSection({element}) {
        if (this.showSharingCartBasket) {
            let backupButton = await this.getBackupToSharingCartButton();

            const sectionTitle = document.querySelector(
                '.course-content .course-section-header .inplaceeditable[data-itemid="' + element.id + '"]'
            );
            if (sectionTitle) {
                const hasBackupButton = sectionTitle.parentElement.querySelector('.add_to_sharing_cart');
                if (!hasBackupButton) {
                    sectionTitle.after(backupButton);

                    backupButton.addEventListener(
                        'click',
                        (e) => {
                            if (e.currentTarget.classList.contains('disabled')) {
                                return;
                            }

                            this.block.addSectionBackupToSharingCart(element.id);
                        }
                    );
                }

                backupButton = sectionTitle.parentElement.querySelector('.add_to_sharing_cart');

                const disabled = element.cmlist.length === 0;
                backupButton.classList.toggle('disabled', disabled);
                backupButton.title = disabled ?
                    await getString('no_course_modules_in_section_description', 'block_sharing_cart') :
                    '';
            }
        }
    }

    /**
     * Refresh the course module.
     * @param {Object} param
     * @param {Object} param.element
     */
    async _refreshCourseModule({element}) {
        if (this.showSharingCartBasket) {
            const backupButton = await this.getBackupToSharingCartButton();

            const courseModuleActionMenu = document.querySelector(
                '.course-content .cm_action_menu[data-cmid="' + element.id + '"]'
            );
            if (!courseModuleActionMenu) {
                setTimeout(() => this._refreshCourseModule({element}), 100);
                return;
            }

            const hasBackupButton = courseModuleActionMenu.querySelector('.add_to_sharing_cart');
            if (!hasBackupButton) {
                courseModuleActionMenu.append(backupButton);

                backupButton.addEventListener(
                    'click',
                    this.block.addCourseModuleBackupToSharingCart.bind(this.block, element.id)
                );
            }
        }
    }
}