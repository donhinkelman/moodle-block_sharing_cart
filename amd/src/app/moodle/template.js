// eslint-disable-next-line no-unused-vars
import BaseFactory from '../factory';
import Templates from "core/templates";
import Fragment from "core/fragment";

export default class Template {
    /**
     * @type {BaseFactory}
     */
    #baseFactory;

    /**
     * @param {BaseFactory} baseFactory
     */
    constructor(baseFactory) {
        this.#baseFactory = baseFactory;
    }

    /**
     * @param {String} template
     * @param {Object} data
     * @return {Promise<
     * {
     *  html: String,
     *  js: String
     * }
     * >}
     */
    async renderTemplate(template, data) {
        return await new Promise((resolve, reject) => {
            Templates.render(template, data)
                .then(async (html, js) => {
                    resolve({
                        html,
                        js
                    });
                }).fail(reject);
        });
    }

    /**
     * @param {String} component
     * @param {String} fragment
     * @param {Number} contextId
     * @param {Object} data
     * @return {Promise<
     * {
     *  html: String,
     *  js: String
     * }
     * >}
     */
    async renderFragment(component, fragment, contextId, data) {
        return await new Promise((resolve, reject) => {
            Fragment.loadFragment(
                component,
                fragment,
                contextId,
                data
            ).then((html, js) => {
                resolve({
                    html,
                    js
                });
            }).fail(reject);
        });
    }

    /**
     * @param {String} js
     */
    runTemplateJS(js) {
        Templates.runTemplateJS(js);
    }

    /**
     * @param {String} template
     * @param {Object} data
     * @return {Promise<HTMLElement>}
     */
    async createElementFromTemplate(template, data) {
        const element = document.createElement('div');

        const {html, js} = await this.renderTemplate(template, data);

        return await Templates.replaceNode(
            element,
            html,
            js
        )[0];
    }


    /**
     * @param {String} component
     * @param {String} fragment
     * @param {Number} contextId
     * @param {Object} data
     * @return {Promise<HTMLElement[]>}
     */
    async createElementsFromFragment(component, fragment, contextId, data) {
        const element = document.createElement('div');

        const {html, js} = await this.renderFragment(component, fragment, contextId, data);

        return await Templates.replaceNode(
            element,
            html,
            js
        );
    }

    /**
     * @param {String} component
     * @param {String} fragment
     * @param {Number} contextId
     * @param {Object} data
     * @return {Promise<HTMLElement>}
     */
    async createElementFromFragment(component, fragment, contextId, data) {
        return (await this.createElementsFromFragment(component, fragment, contextId, data))[0];
    }
}
