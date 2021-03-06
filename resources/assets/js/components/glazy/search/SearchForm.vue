<template>
    <form v-if="isLoaded" class="search-form">
        <div class="form-row">
            <div v-bind:class="sizeMedium" class="form-group">
                <input type="text"
                       class="form-control form-control-sm"
                       v-model="query.params.keywords"
                       placeholder="Search Term"
                       @input="updateKeywords"
                       @keydown.enter.prevent="updateKeywords">
            </div>

            <div v-bind:class="sizeMedium" class="form-group">
                <b-form-checkbox id="must-have-photo"
                                 v-model="query.params.photo"
                                 plain
                                 @change="search">
                    Has photo
                </b-form-checkbox>
            </div>

            <div v-bind:class="sizeMedium" class="form-group">
                <b-form-checkbox id="must-have-photo"
                                 v-model="query.params.state"
                                 value="2"
                                 plain
                                 @change="search">
                    Production only
                </b-form-checkbox>
            </div>

            <div v-bind:class="sizeMedium" class="form-group">
                <b-form-select
                        size="sm"
                        id="baseTypeId"
                        placeholder="Type"
                        v-model="query.params.base_type"
                        :options="baseTypeOptions"
                        @change="searchBaseType">
                    <template slot="first">
                        <option :value="0">All Types</option>
                    </template>
                </b-form-select>
            </div>
            <div v-bind:class="sizeMedium" v-if="subTypeOptions" class="form-group">
                <b-form-select
                        size="sm"
                        v-if="subTypeOptions"
                        id="typeId"
                        placeholder="Subtype"
                        v-model="query.params.type"
                        :options="subTypeOptions"
                        @change="search">
                    <template slot="first">
                        <option :value="0">All Subtypes</option>
                    </template>
                </b-form-select>
            </div>
        </div>
        <div v-if="!isPrimitiveSearch" class="form-row">
            <div v-bind:class="sizeMedium" class="form-group">
                <b-form-select
                        size="sm"
                        id="coneId"
                        placeholder="Temp"
                        v-model="query.params.cone"
                        :options="constants.ORTON_CONES_SELECT_TEXT"
                        @change="search">
                    <template slot="first">
                        <option :value="0">All Temps</option>
                    </template>
                </b-form-select>
            </div>
            <div v-bind:class="sizeMedium" class="form-group">
                <b-form-select
                        size="sm"
                        id="atmosphereId"
                        placeholder="Atmosphere"
                        v-model="query.params.atmosphere"
                        :options="constants.ATMOSPHERE_SELECT"
                        @change="search">
                    <template slot="first">
                        <option :value="0">All Atmospheres</option>
                    </template>
                </b-form-select>
            </div>
        </div>
        <div v-if="!isPrimitiveSearch && isAdvanced" class="form-row">
            <div v-bind:class="sizeMedium" class="form-group">
                <b-form-select
                        size="sm"
                        id="surfaceId"
                        placeholder="Surface"
                        v-model="query.params.surface"
                        :options="constants.SURFACE_SELECT"
                        @change="search">
                    <template slot="first">
                        <option :value="0">All Surfaces</option>
                    </template>
                </b-form-select>
            </div>
            <div v-bind:class="sizeMedium" class="form-group">
                <b-form-select
                        size="sm"
                        id="transparencyId"
                        placeholder="Transparency"
                        v-model="query.params.transparency"
                        :options="constants.TRANSPARENCY_SELECT"
                        @change="search">
                    <template slot="first">
                        <option :value="0">All Transparencies</option>
                    </template>
                </b-form-select>
            </div>
        </div>
        <div v-if="isAdvanced" class="form-row">
            <div v-bind:class="sizeMedium" class="form-group">
                <b-form-select
                        size="sm"
                        id="countryId"
                        placeholder="Country"
                        v-model="query.params.country"
                        :options="countries"
                        @change="search">
                    <template slot="first">
                        <option :value="0">All Countries</option>
                    </template>
                </b-form-select>
            </div>
            <div v-bind:class="sizeMedium" class="form-group">
                <input type="text"
                       class="form-control form-control-sm"
                       v-model="query.params.username"
                       placeholder="User's Name (e.g. Jane)"
                       @input="updateUsername"
                       @keydown.enter.prevent="updateUsername">
            </div>
            <div class="form-group col">
                <b-form-select
                        size="sm"
                        id="yId"
                        placeholder="Y Oxide"
                        v-model="query.params.y"
                        :options="oxides"
                        @change="search">
                </b-form-select>
            </div>
            <div class="form-group col">
                <b-form-select
                        size="sm"
                        id="xId"
                        placeholder="X Oxide"
                        v-model="query.params.x"
                        :options="oxides"
                        @change="search">
                </b-form-select>
            </div>
        </div>
        <div class="form-row">
            <div v-if="!isPrimitiveSearch"
                 v-bind:class="sizeMedium"
                 class="form-group">
                <button @click.prevent="toggleColorPicker"
                        class="btn btn-block search-form-color-button"
                        :style="{'background-color': colorButtonColor, 'color': colorButtonTextColor}"
                        type="button">
                    <i class="fa fa-eyedropper"></i> {{ colorButtonText }}
                </button>
                <chrome-picker v-if="isPickingColor"
                               :value="query.params.color"
                               @input="updateColorValue"></chrome-picker>
            </div>
            <div v-bind:class="sizeMedium" class="form-group">
                <b-button
                        class="search-form-button"
                        variant="secondary"
                        @click.prevent="toggleAdvanced"
                        v-html="advancedButtonText">
                </b-button>
                <b-button
                        class="search-form-button"
                        type="reset"
                        variant="secondary"
                        @click.prevent="resetSearch">
                    <i class="fa fa-refresh"></i> Reset
                </b-button>
            </div>
        </div>
    </form>
</template>

<script>
import SearchQuery from './search-query'
import Analysis from 'ceramicscalc-js/src/analysis/Analysis'
import MaterialTypes from 'ceramicscalc-js/src/material/MaterialTypes'
import GlazyConstants from 'ceramicscalc-js/src/helpers/GlazyConstants'
import { Chrome } from 'vue-color'

import debounce from 'lodash/debounce'

export default {
  name: 'SearchForm',
  components: {
    'chrome-picker': Chrome
  },
  props: {
    isLarge: {
      type: Boolean,
      default: false
    },
    searchQuery: {
      type: Object,
      default: null
    },
    searchUser: {
        type: Object,
      default: null
    }
  },
  data() {
    return {
      // query: new SearchQuery(),
      query: null,
      previousBaseTypeId: null,
      constants: GlazyConstants,
      countries: GlazyConstants.COUNTRY_SELECT,
      oxides: Analysis.OXIDE_NAME_UNICODE_SELECT,
      minSearchTextLength: 3,
      showAdvancedText: '<i class="fa fa-plus"></i> Advanced',
      hideAdvancedText: '<i class="fa fa-minus"></i> Hide Advanced',
      advancedButtonText: '<i class="fa fa-plus"></i> Advanced',
      isAdvanced: false,
      largeSmall: 'col-md-4',
      largeMedium: 'col-md-6',
      largeLarge: 'col-md-12',
      smallSmall: 'col-md-6',
      smallMedium: 'col-md-12',
      smallLarge: 'col-md-12',
      flex: 'col',
      isPickingColor: false
    }
  },
  computed: {
    isLoaded: function() {
      if (this.searchQuery && this.query) {
        return true;
      }
      return false;
    },
    isPrimitiveSearch: function () {
      if (this.$route.name === 'materials' ||
        this.$route.name === 'user-materials') {
        return true
      }
      return false
    },

    sizeSmall: function () {
      if (this.isLarge) {
        return this.largeSmall
      }
      return this.smallSmall
    },

    sizeMedium: function () {
      if (this.isLarge) {
        return this.largeMedium
      }
      return this.smallMedium
    },

    sizeLarge: function () {
      if (this.isLarge) {
        return this.largeLarge
      }
      return this.smallLarge
    },

    baseTypeOptions: function () {
      if (this.isPrimitiveSearch) {
        return MaterialTypes.PRIMITIVE_SELECT;
      }
      return MaterialTypes.COMPOSITE_PARENT_SELECT;
    },

    subTypeOptions: function () {
      if (this.query.params.base_type) {
        if (this.previousBaseTypeId != 0) {
          // we're switching base types.. set type to null
          // TODO: BUG
          // this.query.params.type = 0;
        }
        this.previousBaseTypeId = this.query.params.base_type;
        switch (this.query.params.base_type) {
          case MaterialTypes.GLAZE_TYPE_ID:
            return MaterialTypes.getGlazeTypes();
          case MaterialTypes.CLAYS_TYPE_ID:
            return MaterialTypes.getClayTypes();
          case MaterialTypes.SLIPS_TYPE_ID:
            return MaterialTypes.getSlipTypes();
        }
      }
      return null;
    },

    collectionsSelect () {
      //if (this.$route.name === 'collections' ||
      //  this.$route.name === 'collection') {
        // TODO: ensure only user-viewable collections are returned
        if (this.searchUser && this.searchUser.collections &&
          this.searchUser.collections.length > 0) {
          var collections = []
          this.searchUser.collections.forEach((collection) => {
            collections.push({
            id: collection.id,
            name: collection.name + ' (' + collection.materialCount + ')'
          })
        })
          return collections
          // return this.searchUser.collections
        }
      //}
      return null
    },

    colorButtonText () {
      if (this.isPickingColor) {
        return 'Choose Color'
      }
      return 'Pick a Color'
    },

    colorButtonColor () {
      if ('hex' in this.query.params.color && this.query.params.color.hex) {
        return this.query.params.color.hex
      }
      return '#CCCCCC'
    },

    colorButtonTextColor () {
      // Still don't know the best way to do this
      // Combination of hue & value better
      if (!('hsv' in this.query.params.color)) {
        return '#000000'
      }
      if ((this.query.params.color.hsv.h > 200 && this.query.params.color.hsv.s > 0.8) ||
        (this.query.params.color.hsv.v < 0.5)) {
        return '#FFFFFF'
      }
      return '#000000'
    }

  },
  created() {
    this.query = new SearchQuery();
    if (this.searchQuery) {
      this.query.setParams(this.searchQuery.params)
    }
  },
  watch: {
    searchQuery () {
      if (this.searchQuery) {
        this.query = new SearchQuery();
        this.query.setParams(this.searchQuery.params)
      }
    }
  },
  methods: {
    search: function () {
      this.$emit('searchrequest', this.query);
    },

    updateKeywords: debounce(function (e) {
      console.log('debounce key')
      if (this.query.params.keywords.length >= this.minSearchTextLength) {
        this.query.params.keywords = e.target.value
        this.search()
      } else if (!e.target.value && this.$route.query && 'keywords' in this.$route.query) {
        // There was a keyword search, but now there is not
        // So we still need to search
        this.query.params.keywords = ''
        this.search()
      }
    }, 1400),

    updateUsername: debounce(function (e) {
      if (this.query.params.username.length >= this.minSearchTextLength) {
        this.query.params.username = e.target.value
        this.search()
      } else if (!e.target.value && this.$route.query && 'username' in this.$route.query) {
        // There was a username search, but now there is not
        // So we still need to search
        this.query.params.username = ''
        this.search()
      }
    }, 1400),

    searchBaseType: function () {
      this.query.params.type = 0
      this.search()
    },
    resetSearch: function () {
      // reset search, but keep old base type
      var oldBaseTypeId = this.query.params.base_type
      var newQuery = new SearchQuery()
      newQuery.params.base_type = oldBaseTypeId
      this.$emit('searchrequest', newQuery);
    },
    toggleAdvanced: function () {
      if (this.isAdvanced) {
        this.isAdvanced = false
        this.advancedButtonText = this.showAdvancedText
      }
      else {
        this.isAdvanced = true
        this.advancedButtonText = this.hideAdvancedText
      }
    },

    toggleColorPicker () {
      this.isPickingColor = !this.isPickingColor
      if (!this.isPickingColor && 'hex' in this.query.params.color) {
        this.query.params.hex_color = this.query.params.color.hex.substring(1)
        this.search()
      }
    },

    updateColorValue: function (value) {
      if (value.hex) {
        this.query.params.color = value
      }
    }
  }

}

</script>

<style>

    .search-form {
        margin-bottom: 10px;
    }

    .search-form .form-group {
        margin-top: 0.25rem;
        margin-bottom: 0;
    }

    .search-form .form-control-sm {
        height: 2.25em;
    }

    .search-form-button {
        margin-top: 0px;
        margin-bottom: 0px;
        padding: 11px 16px;
    }

    .search-form-color-button {
        margin-top: 0px;
        margin-bottom: 4px;
        padding: 7px 16px;
    }

</style>