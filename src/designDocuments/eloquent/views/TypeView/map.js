function(doc){
	if(doc.type){
		emit([doc.type, doc._id], [doc._id, doc._rev]);
	}
}