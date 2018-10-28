<template>
  <div class="row calc-component" v-cloak>
    <b-alert v-if="apiError" show variant="danger">
      API Error: {{ apiError.message }}
    </b-alert>
    <b-alert v-if="serverError" show variant="danger">
      Server Error: {{ serverError }}
    </b-alert>
    <div class="load-container load7 fullscreen" v-if="isProcessing">
      <div class="loader">Loading...</div>
    </div>

    <nav class="col-md-3 calc-sidebar d-none d-md-block">

      <div v-if="materials && materials.length > 0 && isLoaded"
           id="umf-d3-chart-container">
        <umf-d3-chart
                :recipeData="chartMaterials"
                :width="chartWidth"
                :height="chartHeight"
                :chartDivId="'umf-d3-chart-container'"
                :colortype="'r2o'"
                :showRecipes="true"
                :showCones="false"
                :showStullChart="true"
                :showStullLabels="false"
                :axisLabelFontSize="'0.75rem'"
                :stullLabelsFontSize="'0.5rem'"
                :showZoomButtons="false"
                :showAxesLabels="true"
                :xOxide="'SiO2'"
                :yOxide="'Al2O3'"
                :highlightedRecipeId="highlightedMaterialId"
                :unHighlightedRecipeId="unHighlightedMaterialId"
        >
        </umf-d3-chart>
      </div>
      <div v-if="materials && materials.length === 0 && isLoaded"
           id="umf-d3-chart-container">
        <umf-d3-chart
                :recipeData="chartMaterials"
                :width="chartWidth"
                :height="chartHeight"
                :chartDivId="'umf-d3-chart-container'"
                :colortype="'r2o'"
                :showRecipes="true"
                :showCones="false"
                :showStullChart="true"
                :showStullLabels="false"
                :axisLabelFontSize="'1rem'"
                :stullLabelsFontSize="'0.75rem'"
                :showZoomButtons="false"
                :showAxesLabels="true"
                :xOxide="'SiO2'"
                :yOxide="'Al2O3'"
                :highlightedRecipeId="highlightedMaterialId"
                :unHighlightedRecipeId="unHighlightedMaterialId"
        >
        </umf-d3-chart>
      </div>

      <div class="row">
        <div class="col-md-12">

          View:
          <select v-model="displayType"
                  class="form-control">
            <option value="umf" selected>UMF</option>
            <option value="percentMol">Mol Percent %</option>
            <option value="percent">Percent %</option>
          </select>

          <button
                  @click.prevent="addRecipeCard"
                  class="btn btn-block btn-sm btn-info">
            <i class="fa fa-plus"></i>
            New Recipe
          </button>

        </div>
      </div>

      <br/>Can't find a material?  <a href="http://help.glazy.org/guide/calculator/#why-are-materials-missing-from-the-calculator" target="_blank">How to add a material to the calculator.</a>

    </nav>

    <main role="main" class="col-md-9 ml-sm-auto calc-results">

      <section v-if="materials && materials.length > 0"
               class="row edit-recipe-card-row">

        <div v-for="(material, index) in materials"
             v-bind:class="editRecipeCardColClass">
          <edit-recipe-card
                  v-if="isLoaded"
                  :selectMaterials="selectMaterials"
                  :material="material"
                  :lookupMaterialLibrary="lookupMaterialLibrary"
                  :displayType="displayType"
                  v-on:materialUpdated="materialUpdated"
                  v-on:checkForDuplicates="checkForDuplicates(index)"
                  v-on:cancelRecipeCard="cancelRecipeCard(index)"
                  v-on:copyMaterial="copyMaterial(index)"
                  v-on:highlightMaterial="highlightMaterial"
                  v-on:unhighlightMaterial="unhighlightMaterial"
                  v-on:updatedMaterialId="updatedMaterialId"
          >
          </edit-recipe-card>
        </div>

      </section>

    </main>

    <b-modal id="similarRecipesModal"
             ref="similarRecipesModalRef"
             size="lg"
             title="Similar Recipes (Limited to first 40)"
    >
      <div v-if="!similarMaterials || !similarMaterials.length">
        <p class="description">No similar recipes found.</p>
      </div>
      <div v-else class="container-fluid">
        <div class="row">
          <div class="col-md-4 col-sm-6" v-for="material in similarMaterials">
            <material-card-detail
                :material="material"
                :isEmbedded="true"
                :showCollapse="true"
            ></material-card-detail>
          </div>
        </div>
      </div>
    </b-modal>

  </div>
</template>

<script>
  import MaterialTypes from 'ceramicscalc-js/src/material/MaterialTypes'
  import Analysis from 'ceramicscalc-js/src/analysis/Analysis'
  import PercentageAnalysis from 'ceramicscalc-js/src/analysis/PercentageAnalysis'
  import Material from 'ceramicscalc-js/src/material/Material'
  import GlazyConstants from 'ceramicscalc-js/src/helpers/GlazyConstants'

  import UmfD3Chart from 'vue-d3-stull-charts/src/components/UmfD3Chart.vue'
  import MaterialCardDetail from '../components/glazy/search/MaterialCardDetail.vue'

  import EditRecipeCard from '../components/glazy/recipe/EditRecipeCard.vue'

  export default {
    name: 'Calculator',
    components: {
      UmfD3Chart,
      MaterialCardDetail,
      EditRecipeCard
    },
    props: {
      //originalMaterials: {
      //  type: Array,
      //  default: () => []
      //}
    },
    directives: { focus: focus },
    data() {
      return {
        originalMaterials: null,
        oxides: GlazyConstants.OXIDE_NAME_UNICODE_SELECT,
        glazetypes: GlazyConstants.GLAZE_TYPES_SELECT,
        colortype: {value:'r2o'},
        materialLibrary: null,
        lookupMaterialLibrary: {},
        selectMaterials: [],
        materials: [],
        chartMaterials: [],
        highlightedMaterialId: {},
        unHighlightedMaterialId: {},
        fakeMaterialIdCounter: 0,
        baseTypeId: MaterialTypes.GLAZE_TYPE_ID,
        isProcessing: false,
        isProcessingDuplicates: false,
        similarMaterials: null,
        chartHeight: 220,
        chartWidth: 0,
        displayType: 'umf',
        editRecipeCardColClass: 'col-md-12',
        initialized: false,
        apiError: null,
        serverError: null,
      };
    },
    computed : {

      isLoaded: function() {
        if (this.selectMaterials.length > 0 && this.materials.length > 0 && this.initialized) {
          return true;
        }
        return false;
      }

    },
    watch: {
      $route (route) {
        if (route.hash) {
          // This is only an internal link, no need to requery
          return
        }
        this.reset();
        // Go to top of window
        window.scrollTo(0, 0)
      }
    },
    mounted() {
      this.reset();

      setTimeout(() => {
        this.handleResize()
      }, 300);
      window.addEventListener('resize', this.handleResize)
    },


    methods: {

      reset: function () {
        this.originalMaterials = [];
        this.materials = [];
        this.chartMaterials = [];

        if (this.$route.query && this.$route.query.id) {
          // http://glazy.test/calculator?id=1&id=2&id=3
          //let querystring = 'id[0]=16830&id[1]=16831';
          let querystring = null;
          if (Array.isArray(this.$route.query.id)) {
            querystring = 'id[]=' + this.$route.query.id.join('&id[]=');
          }
          else {
            querystring = 'id=' + this.$route.query.id;
          }

          let userId = null;
          if (this.$auth.check()) {
            userId = this.$auth.user().id;
          }

          Vue.axios.get(Vue.axios.defaults.baseURL + '/search?' + querystring)
            .then((response) => {
              if (response.data.error) {
              }
              else {
                if (response.data.data && response.data.data.length > 0) {
                  this.originalMaterials = [];
                  response.data.data.forEach((jsonMaterial) => {
                    let material = Material.createFromJson(jsonMaterial);
                    if (userId) {
                      if (jsonMaterial.createdByUserId !== userId) {
                        material.originalId = material.id; // Save the old id for later
                        material.id = this.getFakeMaterialId();
                        material.name = this.getCopyName(material.name);
                      }
                    }
                    material.recalculate();
                    this.originalMaterials.push(material);
                    let newMaterial = material.clone();
                    if ('originalId' in material && material.originalId > 0) {
                      newMaterial.originalId = material.originalId;
                    }
                    this.materials.push(newMaterial);
                    this.chartMaterials.push(newMaterial);
                  });
                  this.fetchPrimitiveMaterials();
                  this.adjustEditRecipeCardSizes();
                  this.initialized = true;
                }
                else {
                  // No materials.  Start with one new recipe
                  let newMaterial = new Material();
                  newMaterial.setName('New Recipe ' + (this.materials.length + 1));
                  this.materials.push(newMaterial);
                  this.chartMaterials.push(newMaterial);
                  this.fetchPrimitiveMaterials();
                  this.initialized = true;
                }
              }
            })
            .catch(response => {
              })
          }
          else {
            // Just start with one new recipe
            let newMaterial = new Material();
            newMaterial.setName('New Recipe ' + (this.materials.length + 1));
            this.materials.push(newMaterial);
            this.chartMaterials.push(newMaterial);
            this.fetchPrimitiveMaterials();
            this.adjustEditRecipeCardSizes();
            this.initialized = true;
          }
      },

      fetchPrimitiveMaterials : function() {
        this.isProcessing = true
        let materialsListUrl = '/usermaterials/editList?';
        if (this.originalMaterials && this.originalMaterials.length > 0) {
          console.log('ORIGIN MAT LEN: ' + this.originalMaterials.length);
          this.originalMaterials.forEach((material) => {
            console.log('herel....: ');
            console.log(material);
            if (material.id > 0) {
              materialsListUrl += '&id[]=' + material.id;
            }
            if (material.originalId > 0) {
              materialsListUrl += '&id[]=' + material.originalId;
            }
          });
        }
        console.log('FETCHING: ' + Vue.axios.defaults.baseURL + materialsListUrl)
        Vue.axios.get(Vue.axios.defaults.baseURL + materialsListUrl)
          .then((response) => {
            if (response.data.error) {
              this.apiError = response.data.error
              console.log(this.apiError)
              this.isProcessing = false
            } else {
              this.isProcessing = false
              this.materialLibrary = response.data.data;
              this.lookupMaterialLibrary = {}
              this.selectMaterials = []
              for (var i = 0; i < this.materialLibrary.length; i++) {
                this.lookupMaterialLibrary[this.materialLibrary[i].id] = this.materialLibrary[i]
                var fullName = this.materialLibrary[i].name
                if (this.materialLibrary[i].otherNames) {
                  fullName += ', ' + this.materialLibrary[i].otherNames
                }
                this.selectMaterials.push({value: this.materialLibrary[i].id, label: fullName})
              }
            }
          })
          .catch(response => {
            if (response.response && response.response.status) {
              if (response.response.status === 401) {
                this.$router.push({ path: '/login', query: { error: 401 }})
              } else {
                this.serverError = response.response.message;
              }
            }
            this.isProcessing = false
          })
      },

      materialUpdated: function () {
        // One of the contained materials was updated
        this.chartMaterials = [];
        this.materials.forEach((material) => {
          this.chartMaterials.push(material);
        });
        /*
        if (this.originalMaterials &&
          this.originalMaterials.length > 0) {
          this.chartMaterials = [];
          //this.chartMaterials = [this.originalMaterials[0]];
          this.materials.forEach((material) => {
            this.chartMaterials.push(material);
          });
        }
        else {
          this.chartMaterials = [];
          this.materials.forEach((material) => {
            this.chartMaterials.push(material);
          });
        }
        */
      },

      checkForDuplicates: function(index) {
        if (!this.isLoaded ||
          index > this.materials.length ||
          !this.materials[index] ||
          !('materialComponents' in this.materials[index])) {
          return
        }
        this.isProcessingDuplicates = true
        this.similarMaterials = null;

        var form = {
          excludeMaterialId: null,
          materialComponents: []
        };

        if (this.originalMaterials &&
          this.originalMaterials.length > 0) {
          // TODO:
          form.excludeMaterialId = this.originalMaterials[0].id;
        }

        // Check each material component for id and amount
        this.materials[index].materialComponents.forEach(function (materialComponent, index) {
          form.materialComponents.push({
            componentMaterialId: materialComponent.material.id,
            percentageAmount: materialComponent.amount,
            isAdditional: materialComponent.isAdditional
          });
        });

        if (form.materialComponents.length > 0) {
          // Only search if we have at least one material component
          Vue.axios.post(Vue.axios.defaults.baseURL + '/search/similarMaterials', form)
            .then((response) => {
              if (response.data.error) {
                console.log('dups error')
                this.apiError = response.data.error
                console.log(this.apiError)
                this.isProcessingDuplicates = false
              } else {
                this.isProcessingDuplicates = false
                this.similarMaterials = response.data.data;
                this.$refs.similarRecipesModalRef.show();
              }
            })
            .catch(response => {
              if (response.response && response.response.status) {
                if (response.response.status === 401) {
                  this.$router.push({ path: '/login', query: { error: 401 }})
                } else {
                  this.serverError = response.response.message;
                }
              }
              this.isProcessingDuplicates = false
            })
        }
        else {
          this.isProcessingDuplicates = false
        }
      },

      addRecipeCard: function () {
        let newMaterial = new Material();
        newMaterial.setName('New Recipe ' + (this.materials.length + 1));
        this.materials.push(newMaterial);
        this.chartMaterials.push(newMaterial);
        this.adjustEditRecipeCardSizes();
      },

      copyMaterial: function (index) {
        if (this.materials && this.materials.length > index) {
          let newMaterial = this.materials[index].clone();
          if (newMaterial.id > 0) {
            newMaterial.originalId = newMaterial.id; // Save the old id for later
          }
          newMaterial.id = this.getFakeMaterialId();
          if (newMaterial.getName()) {
            newMaterial.setName(this.getCopyName(newMaterial.getName()));
          }
          else {
            newMaterial.setName('New Recipe');
          }
          console.log(newMaterial);
          this.materials.push(newMaterial);
          this.chartMaterials.push(newMaterial);
        }
        this.adjustEditRecipeCardSizes();
      },

      handleResize: function () {
        if (this.isLoaded) {
          // this.chartWidth = document.getElementById('umf-d3-chart-container').clientWidth
          var myContainer = document.getElementById('umf-d3-chart-container')
          if (myContainer) {
            this.chartWidth = myContainer.clientWidth
          }
        }
      },

      cancelRecipeCard: function(index) {
        console.log('cancel the card at index: ' + index);
        console.log('material name: ' + this.materials[index].name);
        if (this.materials && this.materials.length > index) {
          this.$delete(this.materials, index);
          this.$delete(this.chartMaterials, index);
        }
        this.adjustEditRecipeCardSizes();
      },

      highlightMaterial: function (id) {
        this.highlightedMaterialId = { id: id }
      },

      unhighlightMaterial: function (id) {
        this.unHighlightedMaterialId = { id: id }
      },

      getFakeMaterialId: function () {
        this.fakeMaterialIdCounter -= 1;
        return this.fakeMaterialIdCounter;
      },

      updatedMaterialId: function (args) {
        // A material's ID changed.  (It was saved.)
        this.materials.forEach((material, index) => {
          if (material.id === args.originalId) {
          console.log("ORIGINAL: " + args.originalId + " NEW: " + args.newId);
            material.id = args.newId;
            this.$set(this.materials, index, material);
            this.$set(this.chartMaterials, index, material);
          }
        });
      },

      adjustEditRecipeCardSizes() {
        if (this.materials && this.materials.length > 1) {
          this.editRecipeCardColClass = 'col-md-6';
        }
        else {
          this.editRecipeCardColClass = 'col-md-12';
        }
      },

      getCopyName: function (currentName) {
        // Get a name for a new or copied recipe.
        // Copied recipes should end with "(Copy)" or
        // "(Copy 2)", "(Copy 3)", etc. when copying a copy.
        if (!currentName) { return 'New Recipe'; }
        let regex = /^(.*)\(Copy\s*(\d*)\)$/;
        var res = currentName.match(regex);
        if (Array.isArray(res)) {
          console.log('is array');
          console.log(res);
          // This is already a copy
          if (res.length === 3 && res[2] && !isNaN(res[2])) {
            return res[1] + '(Copy ' + (parseInt(res[2]) + 1) + ')';
          }
          else if (res[1]) {
            return res[1] + '(Copy 2)';
          }
          else {
            return 'New Recipe';
          }
        }
        else {
          return currentName + ' (Copy)';
        }
      }
    }
  }
</script>

<style>

  .calc-component {
    padding: 0;
    height: 100vh;
  }

  .calc-sidebar {
    background-color: #efefef;
    position: fixed;
    top: 50px;
    bottom: 0;
    left: 0;
    z-index: 1000;
    padding: 0 10px 10px 10px;
    overflow-x: hidden;
    overflow-y: auto; /* Scrollable contents if viewport is shorter than content. */
  }

  .calc-results {
    background-color: #dedede;
    padding-top: 15px;
    padding-bottom: 30px;
  }

  .analysis-col .tabs .nav-tabs {
    padding: 0;
    margin-bottom: 10px;
  }
  .analysis-col .tabs .nav-tabs .nav-item .nav-link {
    padding: 5px 10px;
  }
  .analysis-col .tabs .nav-tabs .nav-item .nav-link.active {
    background-color: #999;
  }
  .analysis-card .card-body {
    padding: 15px 10px 10px 10px;
  }

  .table-analysis-layout tr td {
    padding: 4px;
  }

  .card-umf-info {
    background-color: #cdcdcd;
    max-width: 7em;
    min-width: 6em;
    margin-bottom: 10px;
  }
  .card-umf-info .card-body {
    padding: 5px;
    text-align: center;
  }
  .card-umf-info .card-body .card-title {
    font-size: .8em;
    color: #666666;
    margin: 0;
    text-transform: none;
  }
  .card-umf-info .card-body .card-text {
    font-size: 1.2em;
  }

  .material-analysis-table tr th {
    padding: 0.2rem;
    font-size: 10px;
  }
  .material-analysis-table tr td {
    padding: 0.2rem;
    font-size: 12px;
  }
  .material-analysis-table tr td, .material-analysis-table tr th {
    text-align: right;
  }
  .material-analysis-table tr td.amount {
    font-weight: bold;
  }

  .btn-amount-group {
    margin-left: 2px;
  }

  .btn-amount-group .btn {
    color: #888888;
    font-size: 12px;
    min-width: 24px;
    width: 24px;
    height: 30px;
    line-height: 24px;
    padding: 0;
    margin: 0;
  }

  .amount-input {
    width: 6rem;
  }

  .dropdown .dropdown-menu {
    z-index: 999;
  }
  .v-select .dropdown-menu {
    z-index: 999;
  }

  .similar-materials-row {
    margin-top: -400px;

  }
</style>