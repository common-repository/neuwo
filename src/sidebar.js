/* // eslint-disable no-console */

/*
 * Known issues:
 * - Handles tags internally by converting to lowercase, may cause localisation issues for Turkish etc.
 *   See https://stackoverflow.com/questions/2140627/how-to-do-case-insensitive-string-comparison
 * - Tag quick add buttons can glitch when pressing multiple too fast
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel, PluginPostStatusInfo } from '@wordpress/edit-post';
import apiFetch from '@wordpress/api-fetch';
import { Button, CheckboxControl, Spinner } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useSelect, createReduxStore, register} from '@wordpress/data';
import * as selectors from './selectors'
import * as actions from './actions'
import * as resolvers from './resolvers'
import controls from './controls'

// console.log( 'Running Neuwo Gutenberg Script for sidebar' );

const DEFAULT_STATE = {
	tags: [],
	brand_safety: { BS_score: '', BS_indication: ''},
	marketing_categories: {
		iab_tier_1: [],
		iab_tier_2: [],
	}
}

const store = createReduxStore('neuwo', {
	reducer(state = DEFAULT_STATE, action){
		switch (action.type){
			case 'ADD_TAG':
				return {...state, tags: [...state.tags, action.tag]};  // for demo purposes
			case 'POPULATE_DATA':
				return {...action.neuwoData};
			default:
				return state;
		}
	},
	actions,
	selectors,
	resolvers,  // Side effects for selectors
	controls  // API request
})

register(store);


const NeuwoKeywordsControl = () => {
	const [ loading, setLoading ] = useState(false);

	const neuwoData = useSelect((select) => {
		const neuwoStore = select('neuwo');
		return neuwoStore && neuwoStore.getData();
	}, [])  // TODO runs many times, narrow down depencencies in [] to trigger less times

	const [ addedTags, setAddedTags ] = useState([]);  // Would be better to be reseted during invalidateState after post save
	const [managedErrorNoContent, setManagedErrorNoContentDisplay] = useState(false);
	return (

    // NOTE SlotFill API does not support ordering / priority like action hooks do as of 2024/03
    // Would be better to render under tag box.

		<PluginDocumentSettingPanel title="Neuwo.ai Keywords" icon="tag">

			<Button
			variant="secondary"
			disabled={loading}
			onClick={
				function (){
					setLoading(true);
					setManagedErrorNoContentDisplay(false);
					wp.data.dispatch('core/editor').savePost().then(function(){
						const postId = wp.data.select('core/editor').getCurrentPost().id
						// console.log("Fetching AITopics for post " + postId)

						apiFetch( { path: '/neuwo/v1/getAiTopics?postId=' + postId } ).then( ( apiData ) => {
							// console.log("neuwoData:");
							// console.log(apiData);
							if (apiData.error) {
								console.warn(apiData)
								if (apiData.error.errors && apiData.error.errors.post_content_is_empty) {
									setManagedErrorNoContentDisplay(true);
								}
								setLoading(false);
							} else {
								// wp.data.dispatch('neuwo').populateData(apiData);
								wp.data.dispatch('neuwo').invalidateResolution('getData').then(() => {
									setLoading(false);
								})
							}
						} );
					})
				}
			}>Get keywords</Button>

			{loading && <Spinner />}
			{managedErrorNoContent &&
				<div>
					Blogipostauksen tekstisisältö vaikuttaa tyhjältä.
				</div>}

			<div id="neuwoDataDiv">
			{(neuwoData.tags && neuwoData.tags.length !== 0) &&
				<div id="neuwoAiTopics">
					<h4><strong>AI Topics</strong></h4>
					<ul>
						{neuwoData.tags.map(tag => (
							<li key={tag.value}>{tag.value} <span className="neuwo_tag_score">{tag.score}</span>
							<Button className="button gutenberg_neuwo_add_keyword_as_tag_btn"
									disabled={ tag.addedToPost || addedTags.includes(tag.value) }
									onClick={
										function(){
											setLoading(true);

											return new Promise((resolve, reject) => {
												if (tag.WPTagId) { // if getData endpoint populated tag id for this tag name (in field tag.value)
													resolve(tag.WPTagId)
												} else {
													if (neuwoData.allWPTags) { // if getData listed all tags but didn't WPTagId this, try to find one and resolve it as id
														const existingTag = neuwoData.allWPTags.find(etag => etag.name === tag.value)
														if (existingTag && existingTag.term_id) {
															resolve(existingTag.term_id)
															return;
														}
													}
													// At this point tag with tag.value as name does not exist
													wp.data.dispatch('core').saveEntityRecord('taxonomy', 'post_tag', { name: tag.value }).then((newTag) => {
														if (newTag) resolve(newTag.id)
														else reject("Problem creating a new post_tag via WP Rest API, check the POST request from web browser's Developer Tools' network tab for errors.")
													}, reject)
												}
											}).then((addThisId) => {
												const tags = wp.data.select('core/editor').getEditedPostAttribute('tags').slice();
												if (tags.indexOf(addThisId) < 0) tags.push(addThisId)
												return wp.data.dispatch('core/editor').editPost({ tags })
											}).then(() => {
												setAddedTags(addedTags.concat(tag.value));
												return wp.data.dispatch('neuwo').invalidateResolution('getData').then(() => { // Neuwo Datastore gets updated tag ids for keywords
													setLoading(false);
												});
											}).catch(threw => {
												console.warn('[neuwo/add_keyword_as_tag_btn]', threw);
												setLoading(false);
											})
										}
									}
							>+</Button>
							</li>
						))}
					</ul>
				</div>
			}

			{ (neuwoData.brand_safety && neuwoData.brand_safety.BS_indication) &&
				<div id="neuwoBrandSafety">
					<h4>Brand Safety</h4>
					<p>{neuwoData.brand_safety.BS_indication}</p>
					<p><span className="neuwo_tag_score">{neuwoData.brand_safety.BS_score}</span></p>

				</div>
			}

			{ (neuwoData.marketing_categories &&
			   neuwoData.marketing_categories.iab_tier_1 &&
			   neuwoData.marketing_categories.iab_tier_1.length !== 0) &&
				<div id="neuwoMarketingCategories">
						<h4>IAB Marketing Categories</h4>

						<h5>Tier 1</h5>

						<ul>
							{neuwoData.marketing_categories.iab_tier_1 && neuwoData.marketing_categories.iab_tier_1.map(cat => (
								<li key={cat.value}>{cat.ID} {cat.label} <span className="neuwo_tag_score">{cat.relevance}</span></li>
							))}
						</ul>
						<h5>Tier 2</h5>

						<ul>
							{neuwoData.marketing_categories.iab_tier_2 && neuwoData.marketing_categories.iab_tier_2.map(cat => (
								<li key={cat.value}>{cat.ID} {cat.label} <span className="neuwo_tag_score">{cat.relevance}</span></li>
							))}
						</ul>
				</div>
				}

			</div>

		</PluginDocumentSettingPanel>
	)
}


const NeuwoSimilarityToggleControl = () => {
	const postMeta = useSelect((select) => {
		return select('core/editor').getEditedPostAttribute('meta');
	})
	const [ loading, setLoading ] = useState(false);
	const [isChecked, setChecked] = useState( postMeta.neuwo_exclude_from_similarity )
	return (
		<PluginPostStatusInfo>
			<CheckboxControl
			label="Exclude from Neuwo suggestions"
			disabled={loading}
			checked={isChecked}
			onChange={(newValue) => {
				const postId = wp.data.select('core/editor').getCurrentPost().id
				// console.log('setting to ' + newValue)
				setLoading(true);

				apiFetch({ 	path: '/neuwo/v1/excludeFromSimilarity', method: 'post',
							data: {exclude: newValue.toString(), postId: postId.toString() }
						}).then((apiData) => {
							// console.log("Setting updated apiData");
							// console.log(apiData);
								setChecked(apiData);
								setLoading(false);
							}
						);
			}}
			></CheckboxControl>
		</PluginPostStatusInfo>
	)
}

registerPlugin('neuwo-gutenberg', {
	render: () => {
		return (
			<>

			<NeuwoKeywordsControl/>
			<NeuwoSimilarityToggleControl/>

			</>
		);
	},
});


// TODO remove or redo with new 2023/08 method:
// https://thewpvoyage.com/how-to-detect-when-a-post-is-done-saving-in-wordpress-gutenberg/

// Auto update Neuwo view after post edits
// Has some performance issues while there isn't better official solution yet.
// REF: https://github.com/WordPress/gutenberg/issues/17632#issuecomment-819379829

/**
 * Consults values to determine whether the editor is busy saving a post.
 * Includes checks on whether the save button is busy.
 *
 * @return {boolean} Whether the editor is on a busy save state.
 */
function isSavingPost() {

	// State data necessary to establish if a save is occuring.
	const isSaving = wp.data.select('core/editor').isSavingPost() || wp.data.select('core/editor').isAutosavingPost();
	const isSaveable = wp.data.select('core/editor').isEditedPostSaveable();
	const isPostSavingLocked = wp.data.select('core/editor').isPostSavingLocked();
	const hasNonPostEntityChanges = wp.data.select('core/editor').hasNonPostEntityChanges();
	const isAutoSaving = wp.data.select('core/editor').isAutosavingPost();
	const isButtonDisabled = isSaving || !isSaveable || isPostSavingLocked;

	// Reduces state into checking whether the post is saving and that the save button is disabled.
	const isBusy = !isAutoSaving && isSaving;
	const isNotInteractable = isButtonDisabled && ! hasNonPostEntityChanges;

	return isBusy && isNotInteractable;
  }

// Current saving state. isSavingPost is defined above.
let wasSaving = isSavingPost();

wp.data.subscribe( () => {
	// New saving state
	const isSaving = isSavingPost();

	// It is done saving if it was saving and it no longer is.
	const isDoneSaving = wasSaving && !isSaving;

	// Update value for next use.
	wasSaving = isSaving;

	if ( isDoneSaving ) {
		/**
		 * Add additional functionality here.
		 */

		// Adding a log to understand when this runs.
		// const currentDate = new Date();
		// const time = currentDate.getHours() + ":" + currentDate.getMinutes() + ":" + currentDate.getSeconds();
		// console.log(`${time}Post is done saving via isDoneSaving`);

		// TODO filter when to fetch new neuwoData
		// - if post is published
		// - if neuwoData exists
		wp.data.dispatch('neuwo').invalidateResolution('getData');

	} // End of isDoneSaving

}); // End of wp.data.subscribe
