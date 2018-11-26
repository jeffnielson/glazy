<template>
  <main role="main" class="recipe-print-container">
    <div v-if="apiError" show variant="danger">
      API Error: {{ apiError.message }}
    </div>
    <div v-if="serverError" show variant="danger">
      Server Error: {{ serverError }}
    </div>

    <div v-if="isLoaded">

      <div class="d-flex justify-content-between">
        <div class="flex-grow-1">
          <h1>
            <i v-if="recipe.isPrivate"
               v-b-tooltip.hover title="Private"
               class="fa fa-eye-slash"></i>
            <i v-if="recipe.isArchived"
               v-b-tooltip.hover title="Locked"
               class="fa fa-lock"></i>
            {{ recipe.name }}
          </h1>
        </div>
        <div>
          <h1>
            &#9651;{{ glazyHelper.getConeString(recipe) }}
          </h1>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3 col-lg-4">
          <span v-if="recipe.otherNames">
            Other Names: {{ recipe.otherNames }}
          </span>

          <div v-if="recipe.parentId && recipe.parentName"
               class="row">
            <div class="col-12">
              Parent Material:
              <router-link :to="{ name: 'material', params: { id: recipe.parentId }}">
                {{ recipe.parentName }}
              </router-link>
            </div>
          </div>

          <div>
            Atmospheres: <strong>{{ glazyHelper.getAtmospheresLongString(recipe) }}</strong>
          </div>
          <div>
            Type: <strong>{{ glazyHelper.getMaterialTypeString(recipe) }}</strong>
          </div>
          <div v-if="recipe.materialStateId">
            Status:
            <strong v-if="recipe.materialStateId === 1">Test</strong>
            <strong v-else-if="recipe.materialStateId === 3">Discontinued</strong>
            <strong v-else>Production</strong>
          </div>
          <div v-if="recipe.surfaceTypeName">
            Surface: <strong>{{ recipe.surfaceTypeName }}</strong>
          </div>
          <div v-if="recipe.transparencyTypeName">
            Transparency: <strong>{{ recipe.transparencyTypeName }}</strong>
          </div>
          <div v-if="recipe.countryName">
            Country: <strong>{{ recipe.countryName }}</strong>
          </div>
          <div class="mt-3">
            User: <strong>{{ recipe.createdByUser.name }}</strong>
          </div>
          <div>
            Created: {{ recipe.createdAt }}
          </div>
          <div>
            Updated: {{ recipe.updatedAt }}
          </div>

        </div>
        <div class="col-md-9 col-lg-8">
          <div v-if="!recipe.isPrimitive">
            <material-recipe-calculator
                :materialComponents="recipe.materialComponents"
                :initialBatchSize="batchAmount"
                :isPrint="true">
            </material-recipe-calculator>
          </div>
        </div>
      </div>

      <hr/>

      <div v-if="!recipe.isPrimitive && recipe.baseTypeId == glazeTypeId">
        <h3>
          UMF Analysis
        </h3>

        <div class="d-flex justify-content-between">
          <div>
            <umf-traditional-notation
                :material="material"
                :showOxideList="false"
                :isPrint="true"
                :squareSize="100">
            </umf-traditional-notation>
          </div>
          <div>
            <h6 class="">R<sub>2</sub>O : RO</h6>
            <h2>
              {{ Number(recipe.analysis.umfAnalysis.R2OTotal).toFixed(2) }}
              :
              {{ Number(recipe.analysis.umfAnalysis.ROTotal).toFixed(2) }}
            </h2>
          </div>
          <div>
            <h6 class="card-title">SiO<sub>2</sub> : Al<sub>2</sub>O<sub>3</sub></h6>
            <h2>
              {{ Number(recipe.analysis.umfAnalysis.SiO2Al2O3Ratio).toFixed(2) }}
            </h2>
          </div>
        </div>


      </div>

      <div class="row" v-if="recipe.isPrimitive">
        <div class="col-12 col-sm-12 col-md-auto mb-2">
          Weight:
          <span class="badge badge-info">
                    {{ Number(recipe.analysis.weight).toFixed(3) }}
                  </span>&nbsp;&nbsp;
          Calculated Oxide Weight:
          <span class="badge badge-info">
                    {{ Number(recipe.analysis.oxideWeight).toFixed(3) }}
                  </span>&nbsp;&nbsp;
          <span v-if="'percentageAnalysis' in recipe.analysis">
                    LOI:
                    <span class="badge badge-info">
                      {{ Number(recipe.analysis.percentageAnalysis.loi).toFixed(3) }}
                    </span>
                  </span>
        </div>
      </div>

      <div v-if="recipe.isPrimitive || recipe.isAnalysis" class="row">
        <div class="col-md-12">
          <simple-analysis-table :material="material"></simple-analysis-table>
        </div>
      </div>

      <hr/>
      <h3>
        % Analysis
      </h3>
      <component-table
          :material="material"
          :isFormula="false"
          :isPrint="true">
      </component-table>

      <div v-if="recipe.description">
        <hr/>
        <h3>Notes</h3>
        {{ recipe.description }}
      </div>

    </div>

  </main>

</template>

<script>

  import Material from 'ceramicscalc-js/src/material/Material'

  import MaterialTypes from 'ceramicscalc-js/src/material/MaterialTypes'

  import MaterialTypeBreadcrumbs from '../components/glazy/materialtypes/MaterialTypeBreadcrumbs.vue'
  import FiringCard from '../components/glazy/recipe/FiringCard.vue'
  import MaterialRecipeCalculator from '../components/glazy/recipe/MaterialRecipeCalculator.vue'
  import MaterialImageGallery from '../components/glazy/materialimage/MaterialImageGallery.vue'

  // import JsonUmfSparkSvg from '../components/glazy/analysis/JsonUmfSparkSvg.vue'
  // 20180610 import MaterialAnalysisUmfSpark2Single from '../components/glazy/analysis/MaterialAnalysisUmfSpark2Single.vue';
  import UmfTraditionalNotation from '../components/glazy/analysis/UmfTraditionalNotation.vue';
  import ComponentTable from '../components/glazy/analysis/ComponentTable.vue'
  import SimpleAnalysisTable from '../components/glazy/analysis/SimpleAnalysisTable.vue'

  import UmfChart from '../components/glazy/recipe/UmfChart.vue'
  import SimilarBaseComponentCards from '../components/glazy/recipe/SimilarBaseComponentCards.vue'
  import SimilarUnityFormula from '../components/glazy/recipe/SimilarUnityFormula.vue'
  import ContainsMaterial from '../components/glazy/recipe/ContainsMaterial.vue'

  import GlazyHelper from '../components/glazy/helpers/glazy-helper'

  import EditMaterialMetadata from '../components/glazy/recipe/EditMaterialMetadata.vue'
  import EditRecipeComponents from '../components/glazy/recipe/EditRecipeComponents.vue'

  import ReviewsPanel from '../components/glazy/materialreviews/ReviewsPanel.vue'
  import CommentsPanel from '../components/glazy/materialcomments/CommentsPanel.vue'
  import MaterialCollectionsPanel from '../components/glazy/recipe/MaterialCollectionsPanel.vue'

  import MaterialCardDetail from '../components/glazy/search/MaterialCardDetail.vue'

  import AppFooter from './layout/AppFooter.vue'

  import VueTimeago from 'vue-timeago'

  import Vue from 'vue'

  export default {
    name: 'Recipe',
    metaInfo () {
      return {
        title: this.meta.title,
        meta: [
          {
            'vmid': "description",
            'property': 'description',
            'content': this.meta.description
          },
          {
            'property': 'og:description',
            'content': this.meta.description
          },
          {
            'property': 'og:title',
            'content': this.meta.title
          },
          {
            'property': 'og:url',
            'content': this.meta.url
          },
          {
            'property': 'og:image',
            'content': this.meta.image
          },
          {
            'property': 'og:image:width',
            'content': 800
          },
          {
            'property': 'og:image:height',
            'content': 800
          },
          {
            'property': 'twitter:description',
            'content': this.meta.description
          }
        ]
      }
    },
    components: {
      MaterialTypeBreadcrumbs,
      FiringCard,
      MaterialRecipeCalculator,
      UmfTraditionalNotation,
      SimpleAnalysisTable,
      ComponentTable
    },
    props: {
      recipe_id: {
        type: Number,
        default: null
      },
      current_user: {
        type: Object,
        default: null
      }
    },

    data() {
      return {
        batchAmount: this.$route.params.amount,
        recipe: null,
        material: null,
        glazeTypeId: MaterialTypes.GLAZE_TYPE_ID,
        glazyHelper: new GlazyHelper(),
        isProcessing: false,
        apiError: null,
        serverError: null
      }
    },

    mounted() {
      this.fetchRecipe()
    },
    computed : {
      isLoaded: function() {
        if (this.recipe &&
            this.material &&
            this.recipe.hasOwnProperty('name')
        ) {
          return true;
        }
        return false;
      },
      materialTypeName: function () {
        if (!this.isLoaded) return ''
        if (this.recipe.isPrimitive) return 'Material'
        if (this.recipe.isAnalysis) return 'Analysis'
        return 'Recipe'
      },
      meta: function () {
        var meta = {
          title: 'Recipe',
          description: 'Glazy Ceramics Recipe',
          url: GLAZY_APP_URL + this.$route.fullPath,
        };
        return meta
      }
    },
    methods : {

      fetchRecipe: function (){
        this.sendRecipeGetRequest('/recipes/' + this.$route.params.id)
      },

      sendRecipeGetRequest: function (url) {
        this.apiError = null
        this.isProcessing = true
        Vue.axios.get(Vue.axios.defaults.baseURL + url)
          .then((response) => {
          this.isProcessing = false
          if (response.data.error) {
            this.apiError = response.data.error
            console.log(this.apiError)
          } else {
            this.recipe = null
            this.recipe = response.data.data
            this.meta.title = this.recipe.name
            this.meta.description = this.recipe.name
            this.material = null
            if (this.recipe.isPrimitive || this.recipe.isAnalysis) {
              // Don't calculate formula from percentage
              this.material = Material.createFromJson(this.recipe, false)
            }
            else {
              this.material = Material.createFromJson(this.recipe, true)
            }

            if (this.isEditRequest && this.canEdit) {
              // User just created this recipe in calculator
              this.isEditRequest = false
              this.editMeta()
            }
          }
        })
        .catch(response => {
          this.itemlist = null
          if (response.response && response.response.status) {
            if (response.response.status === 401) {
              this.$auth.refresh() // attempt refresh
            } else {
              this.serverError = response.response.message;
            }
          }
          this.isProcessing = false
        })
      }
    }
  }
</script>


<style>

  .recipe-print-container {
    background-color: #ffffff;
    margin-top: -50px;
    padding: 20px;
  }

</style>