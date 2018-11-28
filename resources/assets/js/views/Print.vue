<template>
  <main v-if="$auth.ready()"
        role="main"
        class="recipe-print-container">
    <div v-if="apiError" show variant="danger">
      API Error: {{ apiError.message }}
    </div>
    <div v-if="serverError" show variant="danger">
      Server Error: {{ serverError }}
    </div>

    <div v-if="isLoaded">
      <div v-for="recipe in recipes"
           v-bind:class="isSimple ? 'recipe-print-multiple' : 'recipe-print-item'">
        <div class="d-flex justify-content-between">
          <div class="flex-grow-1">
            <h1 class="mb-2">
              {{ recipe.name }}
            </h1>
            <span v-if="recipe.otherNames">
              Other Names: {{ recipe.otherNames }}
            </span>
            <h4 class="mt-2">{{ getLink(recipe) }}</h4>
          </div>
          <div>
            <h1 v-html="'&#9651;' + glazyHelper.getConeString(recipe)">
            </h1>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 col-lg-4">
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
              Created:
              {{moment.utc(recipe.createdAt).format('MMMM DD YYYY')}}
            </div>
            <div>
              Updated:
              {{moment.utc(recipe.updatedAt).format('MMMM DD YYYY')}}
            </div>
            <div class="mt-3">
              Current User:
              <br/>
              <strong>{{ $auth.user().name }}</strong>
              <br/>
              Current Date:
              <br/>
              <strong>{{moment().format('MMMM DD YYYY')}}</strong>
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

        <div v-if="!isSimple">
          <div v-if="!recipe.isPrimitive && recipe.baseTypeId == glazeTypeId">
            <h3 class="mt-4">
              UMF Analysis
            </h3>

            <div class="d-flex justify-content-between">
              <div>
                <umf-traditional-notation
                    :material="recipe.material"
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
              <simple-analysis-table :material="recipe.material"></simple-analysis-table>
            </div>
          </div>

          <h3 class="mt-4">
            % Analysis
          </h3>
          <component-table
              :material="recipe.material"
              :isFormula="false"
              :isPrint="true">
          </component-table>

          <div v-if="recipe.description">
            <h3>Notes</h3>
            {{ recipe.description }}
          </div>
        </div>
        <hr class="mb-4 no-print"/>
      </div>
    </div>

  </main>

</template>

<script>

  import Material from 'ceramicscalc-js/src/material/Material'

  import MaterialTypes from 'ceramicscalc-js/src/material/MaterialTypes'

  import MaterialRecipeCalculator from '../components/glazy/recipe/MaterialRecipeCalculator.vue'

  import UmfTraditionalNotation from '../components/glazy/analysis/UmfTraditionalNotation.vue';
  import ComponentTable from '../components/glazy/analysis/ComponentTable.vue'
  import SimpleAnalysisTable from '../components/glazy/analysis/SimpleAnalysisTable.vue'

  import GlazyHelper from '../components/glazy/helpers/glazy-helper'

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
            'property': 'twitter:description',
            'content': this.meta.description
          }
        ]
      }
    },
    components: {
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
        isSimple: this.$route.query.simple,
        batchAmount: this.$route.query.amount,
        materialIds: this.$route.query.id,
        recipes: null,
        materials: null,
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
        if (this.recipes && this.recipes.length) {
          return true;
        }
        return false;
      },
      meta: function () {
        var meta = {
          title: 'Print Recipes',
          description: 'Glazy Ceramics Recipes',
          url: GLAZY_APP_URL + this.$route.fullPath,
        };
        return meta
      }
    },
    methods : {

      fetchRecipe: function (){
        if (this.materialIds) {
          let url = '/recipes/calculatorSearch?';
          if (Array.isArray(this.materialIds) && this.materialIds.length > 0) {
            this.materialIds.forEach((materialId) => {
              url += 'id[]=' + materialId + '&';
            });
          }
          else {
            url += 'id=' + this.materialIds;
          }
          this.sendRecipeGetRequest(url);
        }
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
            this.recipes = null
            this.recipes = response.data.data
            if (this.recipes && this.recipes.length) {
              this.recipes.forEach((recipe) => {
                if (recipe.isPrimitive || recipe.isAnalysis) {
                  // Don't calculate formula from percentage
                  recipe.material = Material.createFromJson(recipe, false)
                }
                else {
                  recipe.material = Material.createFromJson(recipe, true)
                }
              });
            }
          }
        })
        .catch(response => {
          this.recipes = null;
          if (response.response && response.response.status) {
            if (response.response.status === 401) {
              this.$auth.refresh() // attempt refresh
            } else {
              this.serverError = response.response.message;
            }
          }
          this.isProcessing = false
        })
      },

      getLink: function (material) {
        let link = 'https://glazy.org/';
        if (material) {
          if (material.isPrimitive) {
            link += 'materials';
          }
          else if (material.isAnalysis) {
            link += 'analyses';
          }
          else {
            link += 'recipes';
          }
          link += '/' + material.id;
        }
        return link;
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

  .recipe-print-item {
    page-break-after: always;
  }

  .recipe-print-multiple:nth-child(even){
    page-break-after: always;
  }

  @media print {
    body {
      margin-top: 10mm;
      margin-bottom: 5mm;
      margin-left: 5mm;
      margin-right: 5mm;
    }
    .no-print {
      display: none;
    }
    .material-recipe-calculator-table tbody tr td {
      font-size: 1.2rem;
      line-height: 1.8rem;
    }
  }

  @page {
    margin: 0.5cm;
  }

</style>