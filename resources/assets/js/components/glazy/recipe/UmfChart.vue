<template>
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h3>
                    UMF Charts
                    <a href="http://help.glazy.org/concepts/analysis/#unity-molecular-formula-umf" target="_blank" class="help-link"><i class="fa fa-question-circle fa-fw"></i></a>
                </h3>
            </div>
            <div class="col-md-8 text-right">
                <span v-if="isLoaded && !isProcessing && materialList && materialList.length > 0">
                    <em>Search for 100 closest recipes,
                    {{ materialList.length - 1 }} recipes found.</em>
                </span>
            </div>
        </div>
        <div v-if="isLoaded" class="row">
            <div class="col-md-12 col-sm-12 mb-2">
                <img src="/img/charts/recipe.png" height="25"/> Recipe &nbsp;
                <img src="/img/charts/analysis.png" height="25"/> Analysis &nbsp;
                <img src="/img/charts/current.png" height="25"/> Current Recipe &nbsp; &nbsp;
                R<sub>2</sub>O:RO Scale <img src="/img/charts/ror2oscale.png" height="37" width="365"/>
            </div>
            <div class="col-md-8">
                <div class="zoom-buttons" >
                    <b-button @click="toggleZoomable" variant="info" v-if="!isZoomable">
                        <i class="fa fa-search-plus" aria-hidden="true"></i> Allow Pinch & Drag Zoom
                    </b-button>
                </div>
                <div id="umf-d3-chart-container" class="w-100">
                    <umf-d3-chart
                            :recipeData="materialList"
                            :width="chartWidth"
                            :height="chartHeight"
                            :margin="chartMargin"
                            :chartDivId="'umf-d3-chart-container'"
                            :tooltipDivId="'umf-d3-recipe-tooltip'"
                            :currentRecipeId="material.id"
                            :baseTypeId="baseTypeId"
                            :colortype="'r2o'"
                            :showRecipes="true"
                            :showCones="false"
                            :showStullChart="true"
                            :showStullLabels="true"
                            :showZoomButtons="false"
                            :showAxesLabels="true"
                            :showImages="isShowClosestChartImages"
                            :isZoomable="isZoomable"
                            :highlightedRecipeId="{highlightedRecipeId}"
                            :unHighlightedRecipeId="{unHighlightedRecipeId}"
                            :xOxide="xOxide"
                            :yOxide="yOxide"
                            v-on:clickedUmfD3Recipe="clickedD3Chart"
                    >
                    </umf-d3-chart>
                </div>
            </div>
            <div class="col-md-4">
                <div class="load-container load7 floating" v-if="!isLoaded || isProcessing">
                    <div class="loader">Searching...</div>
                </div>
                <div class="form-row">
                    <div class="col-md-12 form-group">
                        <b-form-select
                                id="typeId"
                                size="sm"
                                v-if="materialTypeOptions"
                                v-model="materialTypeId"
                                :options="materialTypeOptions"
                                @input="fetchRecipeList">
                            <template slot="first">
                                <option :value="null">All Recipe Types</option>
                            </template>
                        </b-form-select>
                    </div>
                    <div class="col-md-12 form-group">
                        <b-form-select
                                id="coneId"
                                size="sm"
                                v-model="cone_id"
                                :options="ORTON_CONES_SELECT"
                                @input="fetchRecipeList">
                            <template slot="first">
                                <option :value="null">All Temps</option>
                            </template>
                        </b-form-select>
                    </div>
                    <div class="form-group col-sm-6">
                        <b-form-select
                                id="xOxide"
                                size="sm"
                                v-model="xOxide"
                                :options="oxides"
                                @input="fetchRecipeList"
                                class="col">
                        </b-form-select>
                    </div>
                    <div class="form-group col-sm-6">
                        <b-form-select
                                id="yOxide"
                                size="sm"
                                v-model="yOxide"
                                :options="oxides"
                                @input="fetchRecipeList"
                                class="col">
                        </b-form-select>
                    </div>

                    <div class="col-md-12 form-group">
                        <b-form-checkbox id="isShowClosestChartImagesCheckbox"
                                         v-model="isShowClosestChartImages"
                                         plain>
                            Show images
                        </b-form-checkbox>
                    </div>


                    <div v-if="$auth.check()" class="col-md-12 form-group">
                        <b-form-checkbox id="isMineCheckbox"
                                         v-model="isMine"
                                         plain>
                            Only show my items
                        </b-form-checkbox>
                    </div>

                </div>
                <div class="col-sm-12">
                    <div v-if="clickedRecipe"
                         class="card chart-recipe-card">
                        <div class="card-body">
                            <p v-html="clickedRecipe.name"></p>
                            <a v-bind:href="'https://glazy.org/recipes/' + clickedRecipe.id"
                               target="_blank" class="btn">View on Glazy</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</template>

<script>

  import Vue from 'vue'
  import Analysis from 'ceramicscalc-js/src/analysis/Analysis'
  import MaterialTypes from 'ceramicscalc-js/src/material/MaterialTypes'
  import GlazyConstants from 'ceramicscalc-js/src/helpers/GlazyConstants'
  import UmfD3Chart from 'vue-d3-stull-charts/src/components/UmfD3Chart.vue'

  export default {

    name: 'UmfChart',
    components: {
      UmfD3Chart
    },
    props: {
      material: {
        type: Object,
        default: null
      }
    },
    data() {
      return {
        materialList: null,
        chart: null,
        ORTON_CONES_SELECT: GlazyConstants.ORTON_CONES_SELECT,
        oxides: Analysis.OXIDE_NAME_UNICODE_SELECT,
        materialTypeId: null,
        cone_id: null,
        showConesString: 'Show Cones',
        isProcessing: true,
        yOxide: 'Al2O3',
        xOxide: 'SiO2',
        oxide3: 'Fe2O3',
        baseTypeId: MaterialTypes.GLAZE_TYPE_ID,
        noZeros: false,
        isThreeAxes: false,
        showStullChart: true,
        isZoomable: false,
        isShowClosestChartImages: false,
        isMine: false,
        chartHeight: 340,
        chartWidth: 0,
        chartMargin: {
          left: 24,
          right: 10,
          top: 0,
          bottom: 12
        },
        axesColor: '#aaaaaa',
        gridColor: '#aaaaaa',
        highlightedRecipeId: 0,
        unHighlightedRecipeId: 0,
        showModeBar: 'false',
        clickedRecipe: null,
        currentPage: 1,
        timeout: null
      }
    },
    computed: {
      isLoaded: function () {
        if (this.materialList && this.materialList.length > 0) {
          return true;
        }
        return false;
      },

      hasRecipeList: function () {
      },

      materialTypeOptions: function () {
        if (this.material.baseTypeId) {
          switch (this.material.baseTypeId) {
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

      umf_analysis: function () {
        if (this.isLoaded) {
          if (this.material.hasOwnProperty('analysis')
            && this.material.analysis
            && this.material.analysis.hasOwnProperty('umf_analysis')
            && this.material.analysis.umf_analysis) {
            return this.material.analysis.umf_analysis;
          }
        }
        return null;
      },

      type_options: function () {
        return MaterialTypes.getGlazeTypes();
      }
    },

    mounted() {
      // TODO: assumes material already loaded
      // TODO: for some reason the mounted function being called twice!
      if (this.material) {
        if (this.material.materialTypeId) {
          this.materialTypeId = this.material.materialTypeId;
        }
        this.fetchRecipeList();
      }

      //this.chartHeight = document.getElementById('umf-d3-chart-container').clientHeight
      //this.chartWidth = document.getElementById('umf-d3-chart-container').clientWidth

      this.timeout = setTimeout(() => {
        this.handleResize()
      }, 500);
      window.addEventListener('resize', this.handleResize)
    },
    beforeDestroy() {
      clearTimeout(this.timeout);
      window.removeEventListener('resize', this.handleResize);
    },
    watch: {
      material: function (val) {
        if (this.material) {
          if (this.material.materialTypeId) {
            this.materialTypeId = this.material.materialTypeId
          }
          this.isZoomable = false
          this.fetchRecipeList()
        }
      },
      isMine: function (val) {
        this.fetchRecipeList();
      }
    },

    methods: {

      fetchRecipeList: function () {

        this.isProcessing = true;

        var recipeUrl = Vue.axios.defaults.baseURL + '/search/nearestXY?material_id=' + this.material.id;
        recipeUrl += '&y=' + this.yOxide
        recipeUrl += '&x=' + this.xOxide
        if (this.materialTypeId) {
          recipeUrl += '&material_type_id=' + this.materialTypeId;
        }
        if (this.cone_id) {
          recipeUrl += '&cone=' + this.cone_id;
        }
        if (this.isMine) {
          recipeUrl += '&isMine=true';
        }

        Vue.axios.get(recipeUrl)
          .then(function (response) {
            this.materialList = response.data.data;
            if (!this.materialList) {
              this.materialList = [];
            }
            this.materialList.push(this.material)

            /*
            TODO:  Add ability to show or sort by current user
            var currentUserId = null;
            if (this.current_user && this.current_user.hasOwnProperty('id')) {
              currentUserId = this.current_user.id;
            }
            */
            this.isProcessing = false;
          }.bind(this), function (response) {
            this.isProcessing = false;
          }.bind(this));
      },

      clickedD3Chart (data) {
        this.clickedRecipe = data
      },

      handleResize: function () {
        if (document.getElementById('umf-d3-chart-container')) {
          // this.chartHeight = document.getElementById('umf-d3-chart-container').clientHeight
          this.chartWidth = document.getElementById('umf-d3-chart-container').clientWidth
        }
      },

      toggleZoomable: function (d) {
        this.isZoomable = !this.isZoomable
      },

      coneString: function(fromOrtonConeName, toOrtonConeName) {
        var coneString = '?';
        if (fromOrtonConeName
          && toOrtonConeName
          && fromOrtonConeName != toOrtonConeName) {
          return fromOrtonConeName + "-" + toOrtonConeName;
        }

        if (fromOrtonConeName) {
          return coneString = fromOrtonConeName;
        }

        if (toOrtonConeName) {
          coneString = toOrtonConeName;
        }
        return coneString;
      }
    }
}

</script>

<style>
    .chart-recipe-card {
        background-color: #cccccc;
    }

    .similar-table {
        height: 200px !important;
        overflow: auto;
    }

    .r2o-colors tr td {
        font-size: 12px;
        min-width: 26px;
        margin: 1px;
        text-align: center;
    }
    .r2o-colors tr td.label {
        width: 100px;
    }
    .zoom-buttons {
        right: 40px !important;
        top: 10px !important;
    }
    
</style>