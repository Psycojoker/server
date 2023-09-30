<!--
	- @copyright 2023 Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license AGPL-3.0-or-later
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<table class="user-list" data-cy-user-list>
		<thead ref="thead"
			role="rowgroup"
			class="user-list__header"
			data-cy-user-list-thead>
			<slot name="header" />
		</thead>

		<tbody :style="tbodyStyle"
			class="user-list__body"
			data-cy-user-list-tbody>
			<component :is="dataComponent"
				v-for="(item, i) in renderedItems"
				:key="i"
				:visible="(i >= bufferItems || index <= bufferItems) && (i < shownItems - bufferItems)"
				:user="item"
				:index="i"
				v-bind="extraProps" />
		</tbody>

		<tfoot v-show="isReady"
			ref="tfoot"
			role="rowgroup"
			class="user-list__footer"
			data-cy-user-list-tfoot>
			<slot name="footer" />
		</tfoot>
	</table>
</template>

<script lang="ts">
import Vue from 'vue'
import { debounce } from 'debounce'

import logger from '../../logger.js'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type User = Record<any, any>

// Items to render before and after the visible area
const bufferItems = 3

export default Vue.extend({
	name: 'VirtualList',

	props: {
		dataComponent: {
			type: [Object, Function],
			required: true,
		},
		dataKey: {
			type: String,
			required: true,
		},
		dataSources: {
			type: Array,
			required: true,
		},
		itemHeight: {
			type: Number,
			required: true,
		},
		extraProps: {
			type: Object,
			default: () => ({}),
		},
	},

	data() {
		return {
			bufferItems,
			index: 0,
			headerHeight: 0,
			tableHeight: 0,
			resizeObserver: null as ResizeObserver | null,
		}
	},

	computed: {
		// Wait for measurements to be done before rendering
		isReady() {
			return this.tableHeight > 0
		},

		startIndex() {
			return Math.max(0, this.index - bufferItems)
		},

		shownItems() {
			return Math.ceil((this.tableHeight - this.headerHeight) / this.itemHeight) + bufferItems * 2
		},

		renderedItems(): User[] {
			if (!this.isReady) {
				return []
			}
			return this.dataSources.slice(this.startIndex, this.startIndex + this.shownItems)
		},

		tbodyStyle() {
			const isOverScrolled = this.startIndex + this.shownItems > this.dataSources.length
			const lastIndex = this.dataSources.length - this.startIndex - this.shownItems
			const hiddenAfterItems = Math.min(this.dataSources.length - this.startIndex, lastIndex)
			return {
				paddingTop: `${this.startIndex * this.itemHeight}px`,
				paddingBottom: isOverScrolled ? 0 : `${hiddenAfterItems * this.itemHeight}px`,
			}
		},
	},

	mounted() {
		const root = this.$el as HTMLElement
		const tfoot = this.$refs?.tfoot as HTMLElement
		const thead = this.$refs?.thead as HTMLElement

		this.resizeObserver = new ResizeObserver(debounce(() => {
			this.headerHeight = thead?.clientHeight ?? 0
			this.tableHeight = root?.clientHeight ?? 0
			logger.debug('VirtualList resizeObserver updated')
			this.onScroll()
		}, 100, false))

		this.resizeObserver.observe(root)
		this.resizeObserver.observe(tfoot)
		this.resizeObserver.observe(thead)

		this.$el.addEventListener('scroll', this.onScroll)
	},

	beforeDestroy() {
		if (this.resizeObserver) {
			this.resizeObserver.disconnect()
		}
	},

	methods: {
		onScroll() {
			// Max 0 to prevent negative index
			this.index = Math.max(0, Math.round(this.$el.scrollTop / this.itemHeight))

			if (this.index >= this.dataSources.length) {
				this.$emit('scroll-end')
			}
		},
	},
})
</script>

<style lang="scss" scoped>

</style>
